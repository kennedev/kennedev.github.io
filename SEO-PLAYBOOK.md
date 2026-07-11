# SEO / Performance Playbook — repo Portifolio

Guia para tratar pedidos de SEO/performance nos sites deste repositório.
Escrito a partir de um mutirão real (jul/2026). Leia antes de "sair consertando".

---

## 0. Mentalidade (leia primeiro)

- **Ferramentas tipo RD Station analyzer são isca de funil.** A "nota %" mistura coisas
  rasas e existe pra vender curso. Não a trate como métrica de qualidade — use só como pista.
- **SEO técnico ≠ ranking.** Title/description/OG/JSON-LD/minificação são **higiene**:
  removem atrito, não geram demanda. O que move o orgânico, em ordem de ROI:
  1. **Google Meu Negócio / Perfil da Empresa** (negócios locais) — maior retorno, fora do código.
  2. **Estar indexado** — sitemap/robots + Search Console.
  3. **Conteúdo** que responde à intenção de busca (site de 1 página tem teto baixo).
  4. **Backlinks**.
  5. Higiene técnica (o que dá pra fazer no código).
- Diga isso ao usuário. Não deixe ele perseguir o "40%".

## 1. Escopo: sites reais vs. teste (CRÍTICO — confirme antes)

O repo mistura **um site publicado na raiz** com várias pastas que são **testes locais**.
Sempre confirme com o usuário antes de gastar esforço.

- **Reais/publicados:** `./` (Kennedev, em `kennedev.com.br`, Hostinger),
  `imobiliaria/` (`www.imobiliarialuisimoveis.com.br`), `apps/` (`kennedev.com.br/apps`),
  `mabarbearia/` (`mabarbearia.com.br`).
- **Área logada (NÃO indexar — só `noindex`):** `admin/`.
- **Testes locais (confirmar caso a caso):** `tecnopest/`, `telafacil/`, `verbuz/`,
  `vicente/`, `casaquadradaimobiliaria/`.
- Cada subsite tem seus próprios assets; a pasta `img/` da raiz é **do Kennedev**.

## 2. Auditoria rápida (o que checar por site)

Rode um agente Explore ou greps por: `<title>` (+ tamanho, ideal ≤60), meta description
(≤160), `<html lang>` (bugs comuns: `zxx`, `pt-br`), canonical, `og:*`, JSON-LD,
`loading="lazy"`, e **imagens pesadas**:

```bash
find <site> -type f \( -iname "*.jpg" -o -iname "*.png" \) -size +300k \
  -printf '%s\t%p\n' | sort -rn | awk '{printf "%.2fMB\t%s\n",$1/1048576,$2}'
```

Referência de site "bem feito" neste repo: **`telafacil/index.html`** (canonical + og:* completo).
Sites PHP: o `<head>` costuma ser gerado em helper compartilhado
(`imobiliaria/lib/publico.php` → `site_head()`, `admin/lib/layout.php` → `layout_head()`);
`apps/index.php` tem head inline.

## 3. Ferramentas de imagem (maior ROI de performance)

Nenhum conversor instalado (`cwebp`/`magick` ausentes). Use **Node + `sharp`** no scratchpad:

```bash
cd <scratchpad> && npm init -y && npm install sharp
```

Padrões que funcionaram:
- **JPG grande** → recomprimir/redimensionar **no mesmo arquivo e formato** (zero mudança
  de ref): `sharp(p).resize({width:1920,fit:'inside',withoutEnlargement:true}).jpeg({quality:78,mozjpeg:true,progressive:true})`.
- **PNG de foto** → converter para **WebP** e **atualizar refs** por *basename* em todos
  os `.html/.css/.js` do site (ex.: `t.c.split('x.png').join('x.webp')`), depois apagar o PNG.
  Ganhos reais: um mutirão levou o `mabarbearia` de **22 MB → 1,9 MB**.
- **Logos PNG** → redimensionar + otimizar in-place, manter transparência.
- Sempre **backup dos originais** no scratchpad antes de sobrescrever.
- Depois de converter, cheque órfãos (imagens sem referência) e confirme que nenhum ref
  ficou apontando pra arquivo apagado.

### Gotchas de imagem (Windows)
- **Cross-drive copy falha** (`copyfile UNKNOWN`, errno -4094) ao mover de `C:\Users\...temp`
  pra `c:\Codes`. Grave o temp **no mesmo diretório do alvo** e use `fs.renameSync`.
- **Heredoc come `\`** em regex. Escreva scripts JS com a ferramenta Write, não via `cat <<EOF`.

## 4. Ícones sem CDN render-blocking

Problema clássico no Kennedev: `devicon@latest` (fonte inteira + CSS bloqueante + versão
instável). **ionicons já estava OK** (pinado `@7.1.0` + `type=module` = deferred; não é
render-blocking — não mexa à toa).

Solução aplicada: **sprite SVG inline**.
1. Baixe os SVGs coloridos (`curl` do jsdelivr). Namespace IDs internos por ícone (gradientes
   colidem entre `<symbol>`s).
2. Monte um `<svg style="position:absolute;width:0;height:0">` com `<symbol id="dv-x" viewBox=...>`
   logo após `<body>`; troque cada `<i class="devicon-x-plain colored">` por
   `<svg class="tech-ico" width="40" height="40"><use href="#dv-x"></use></svg>`.
3. **`width/height` nos SVGs não é opcional**: sem eles, SVG sem dimensão intrínseca assume
   300×150 e quebra o layout (flex "incha") **e** vira fallback se o CSS estiver em cache.
4. CSS: `.tech-ico{height:2.6rem;width:auto}` (font-size não dimensiona SVG!). Replique os
   overrides responsivos que miravam `i`.

### Gotcha AWS
O devicon atual **não tem** AWS quadrado (`-original`/`-plain` dão 403; só `-wordmark`, feio).
O logo de "cubos" (`devicon@v2.15.1`) é **antigo/aposentado**. Use o logo **atual** ("aws" +
smile) do simple-icons e bake a cor: `simple-icons@13/icons/amazonwebservices.svg`, forçar
`fill="#FF9900"`.

## 5. Open Graph, canonical, JSON-LD

- Espelhe o padrão do TelaFacil. `og:image` ideal 1200×630.
- **Gerar OG image branded** com sharp (SVG→PNG) funciona e renderiza fontes do sistema
  (`Segoe UI`/`Arial`). Use as cores da marca (Kennedev: `#070b16`/`#6366f1`/`#22d3ee`).
- JSON-LD por tipo: `Organization`/`WebSite` (empresa), `LocalBusiness`/`HairSalon` (negócio
  local — inclua `address`, `telephone`, `openingHoursSpecification`), `FAQPage` (páginas de FAQ),
  `RealEstate`/`Residence` (imóveis). Sempre valide com `JSON.parse` após inserir.

## 6. sitemap.xml + robots.txt

- Nenhum site tinha. Um por domínio, na raiz do docroot daquele site.
- Estático (`.xml`) pra sites de páginas fixas; **dinâmico (`.php`)** quando há páginas de
  banco (ex.: `imobiliaria/sitemap.php` enumera imóveis `status='disponivel'`).
- `robots.txt`: `Allow: /`, `Disallow:` a área admin, e `Sitemap:` com URL absoluta.

## 7. Verificação (faça antes de declarar pronto)

- **Estrutural:** todo `<use href="#x">` tem `<symbol id="x">`; JSON-LD dá `JSON.parse`;
  sitemap tem `</urlset>`; 0 ocorrências de `lang="zxx"`, títulos placeholder, `55XXX`, etc.
- **Visual (headless Edge, existe no Win11):**
  ```bash
  "/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe" --headless=new \
    --disable-gpu --hide-scrollbars --window-size=1280,900 \
    --screenshot=out.png "file:///c:/Codes/Portifolio/<site>/index.html"
  ```
  Gotchas: seções com `.reveal`/animação (WOW/IntersectionObserver) ficam invisíveis em
  headless — injete `.reveal{opacity:1!important;transform:none!important}` numa cópia
  `__vtest.html` (mesma pasta, pra paths relativos funcionarem) e apague depois. `.hero` com
  `min-height:100vh` estica com janela alta e empurra o resto — cape a altura na cópia de teste.

## 8. O que é do usuário, não do código (deixe explícito)

- **Perfil da Empresa (Google Meu Negócio)** — prioridade nº 1 para negócios locais.
- **Search Console**: verificar domínio, enviar sitemaps, "Solicitar indexação".
- **Hostinger**: ativar LiteSpeed Cache no hPanel resolve "tempo de resposta do servidor"
  (é hospedagem, não código). Minificação de CSS/JS num site pequeno (~40 KB) não compensa
  quebrar o stack vanilla.

## 9. Prazos (expectativa realista)

| O que | Quando (após **deploy + purge de cache**) |
|---|---|
| RD/PageSpeed/Lighthouse/Rich Results/OG debugger | Na hora (leem a página ao vivo) |
| Preview OG no WhatsApp | Cacheado; forçar no Facebook Sharing Debugger |
| Google indexar páginas novas | Horas a dias (com Search Console) |
| Subir no ranking orgânico | Semanas+ |

Nada é reconhecido enquanto **não estiver publicado** — mudanças no working tree local não contam.
