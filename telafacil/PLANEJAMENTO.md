# PLANEJAMENTO — Site institucional TelaFacil Redes de Proteção

## 0. Stack aprovada (revisão)

- **Permitido:** HTML5, **Bootstrap 5** (navbar, grid, cards, accordion, botões, utilitários), **CSS customizado** em [assets/css/style.css](assets/css/style.css), **JavaScript** em [assets/js/main.js](assets/js/main.js), **jQuery** apenas se simplificar (formulário, scroll, galeria, classes) — **sem plugins jQuery externos**.
- **Bootstrap via CDN (todas as páginas):** carregar o CSS 5.x e o **Bundle JS** (inclui Popper) do mesmo major 5. **Não** usar Bootstrap 4.
- **Não usar:** Node.js, npm, `package.json`, `node_modules`, Vite, Webpack, Parcel, scripts de build, servidor de desenvolvimento backend, React, Vue, Svelte, Tailwind, Angular, CMS, banco de dados, leitura dinâmica de pastas no navegador. O site abre com **HTML direto** no navegador ou em hospedagem estática; dependências (Bootstrap, jQuery) apenas **via CDN** no HTML, sem instalação.
- **Identidade:** o visual final deve ser **institucional e próprio da TelaFacil Redes de Proteção** (sobrescritas em `style.css`); evitar aparência genérica de template Bootstrap.
- **Galeria:** lista **fixa** de arquivos no HTML/JS; inspecionar nomes reais (15 imagens em `assets/img/carrossel-servicos/`, listadas na seção 5).
- **Footer:** ano com **JavaScript** (`new Date().getFullYear()`) no elemento `#ws-year` — **não** fixar 2025.
- **Canonical / OG:** somente URLs absolutas `https://telafacil.com.br/...` (sem caminhos locais tipo `c:\`).

## 1. Objetivo do site

Apresentar a **TelaFacil Redes de Proteção** como fornecedora de redes e telas de proteção para janelas, sacadas, apartamentos, famílias e pets; transmitir profissionalismo, segurança e clareza; e **converter visitantes em orçamentos via WhatsApp** (5511990077134), com formulário que monta a mensagem automaticamente.

## 2. Arquitetura de páginas

| Página | Arquivo | Função |
|--------|---------|--------|
| Home | [index.html](index.html) | Proposta de valor, credenciais, serviços, especificações, processo, formulário, galeria, FAQ resumido, CTA |
| Serviços | [servicos.html](servicos.html) | Listagem dos serviços e benefícios; CTA orçamento |
| Quem somos | [quem-somos.html](quem-somos.html) | Institucional; confiança sem inventar números ou endereço |
| FAQ | [faq.html](faq.html) | 12 perguntas obrigatórias em accordion |
| Contato | [contato.html](contato.html) | Canais, mesmo formulário de orçamento da home |

Navegação cruzada: header e footer em todas as páginas; links internos com caminhos relativos (`*.html`).

## 3. Estrutura de seções por página

**index.html (ordem):** Header — Hero (1 imagem) — Barra de credenciais — Serviços (6 cards) — Especificações técnicas — Como funciona (6 passos) — Orçamento (`#orcamento`) — Galeria — FAQ curto (5–6 itens + link para FAQ) — CTA final — Footer.

**servicos.html:** Header — Hero interno — Grid de 6 serviços — Benefícios — CTA orçamento — Footer.

**quem-somos.html:** Header — Hero interno — Texto institucional — Compromisso com segurança — Como trabalhamos — CTA — Footer.

**faq.html:** Header — Hero interno — Accordion (12 itens) — CTA orçamento — Footer.

**contato.html:** Header — Hero interno — Botões de contato — Formulário (equivalente ao da home) — Informações e horário neutro — Footer.

## 4. Componentes reutilizáveis

- **Header:** Bootstrap 5 `navbar` expandível; logo, links, botão WhatsApp.
- **Footer:** bloco escuro, dados de contato, links, copyright com **ano inserido por JavaScript** (ou 2026 estático; não usar 2025 fixo).
- **CTA WhatsApp flutuante:** link fixo `https://wa.me/5511990077134`, posição segura no mobile.
- **Formulário de orçamento:** mesma marcação e `id="orcamento"` em `index.html` e `contato.html` (cada documento é isolado; sem conflito de ID entre páginas carregadas separadamente).
- **Galeria:** trilha com as 15 imagens listadas de forma **fixa** no HTML; JavaScript apenas controla rolagem horizontal (mobile) e botões anterior/próximo.

## 5. Estratégia de imagens

- **Logos:** [assets/img/logo-oficial.png](assets/img/logo-oficial.png) no header; [assets/img/logo-com-fundo-branco.png](assets/img/logo-com-fundo-branco.png) alternativa se necessário para contraste.
- **Hero:** uma imagem forte de sacada — `sacada-grande.jpeg` (ou `foto-de-uma-sacada-grande.jpeg`); `loading` eager, sem lazy na hero.
- **Cards de serviços:** mapeamento explícito (janelas, sacadas, apartamentos, crianças, pets, telas) com arquivos indicados no briefing; imagens **locais apenas**.
- **Galeria:** lista fechada de 15 arquivos em `assets/img/carrossel-servicos/` (ver checklist abaixo). **Não** há leitura de diretório no navegador; todos os `src` estão no HTML (ou reforçados no JS para navegação).

**Lista fixa de arquivos da galeria**

- `foto-de-uma-sacada-grande.jpeg`
- `foto-janela-de-apartamento.jpeg`
- `gatinho-filhote-na-janela.jpeg`
- `janela-02.jpeg`
- `janela-com-gatinho-preto.jpeg`
- `janela-de-apartamento.jpeg`
- `janela-de-ape.jpeg`
- `janela-de-quarto.jpeg`
- `janela-em-apartamento.jpeg`
- `janela-grande-02.jpeg`
- `janela-grande.jpeg`
- `janela-media.jpeg`
- `janelinha.jpeg`
- `sacada-grande.jpeg`
- `sacadinha-de-apartamento.jpeg`

## 6. Estratégia de CSS

- **Bootstrap 5** (CDN) para grid, navbar, cards, botões, utilitários e accordion.
- **Custom:** [assets/css/style.css](assets/css/style.css) carregado **após** o Bootstrap — variáveis CSS (`:root`) para verde da marca, cinzas, tipografia, raios, sombras; overrides de cores primárias para não parecer “template Bootstrap padrão”; espaçamento de seções, hero, footer, galeria (grid em desktop, scroll em mobile), foco visível, imagens com cantos discretos.

## 7. Estratégia de JavaScript

- **Bootstrap 5 Bundle** (CDN) para collapse do menu e accordion nativo.
- **jQuery** (CDN) opcional para: fechar navbar ao clicar em link, tecla Escape, scroll suave, formulário de orçamento, galeria (scroll), ano no footer — apenas se reduzir código; **sem plugins jQuery**.
- **main.js** ([assets/js/main.js](assets/js/main.js)): validação do formulário, montagem condicional da mensagem WhatsApp, `encodeURIComponent`, `window.open` com `noopener` e `noreferrer`; inicialização da galeria; `document.getElementById` para ano; checagem defensiva se elementos existem.
- Não usar React, Vue, Svelte, Angular, build tools, backend ou CMS.

## 8. SEO básico por página

- **Canonical e URLs:** sempre absolutas com `https://telafacil.com.br/` + caminho (ex.: `/`, `/servicos.html`, `/quem-somos.html`, `/faq.html`, `/contato.html`). Nunca URL de disco local.
- Cada página: `title` único, `meta name="description"`, Open Graph (`og:title`, `og:description`, `og:url`, `og:type`, `og:image` com URL absoluta do logo no domínio).
- Um `h1` por página; imagens com `alt` descritivo.

| Página | Title sugerido |
|--------|----------------|
| Home | TelaFacil Redes de Proteção \| Redes de Proteção para Janelas e Sacadas |
| Serviços | Serviços \| TelaFacil Redes de Proteção |
| Quem somos | Quem Somos \| TelaFacil Redes de Proteção |
| FAQ | Perguntas Frequentes \| TelaFacil Redes de Proteção |
| Contato | Contato \| TelaFacil Redes de Proteção |

## 9. Checklist de implementação

- [x] PLANEJAMENTO.md, cinco HTML, `style.css`, `main.js`
- [x] Bootstrap 5 CSS + Bundle (CDN), sem Bootstrap 4
- [x] jQuery apenas se usado, sem plugins externos
- [x] Todos os links internos e `assets/` funcionando
- [x] Formulário: validação + mensagem WhatsApp com regras de linhas opcionais
- [x] Menu mobile acessível; footer com ano dinâmico ou 2026
- [x] Galeria com lista fixa de 15 imagens; sem autoplay obrigatório
- [x] Sem Lorem Ipsum, sem dados inventados, sem depoimentos falsos, sem selo Inmetro falso

## 10. Decisões e justificativas

- **Bootstrap 5:** agiliza layout responsivo e componentes; identidade vem do CSS custom e conteúdo, não do tema padrão.
- **CDN:** conforme requisito do projeto; reduz tamanho do repositório.
- **Lista de imagens fixa no HTML:** sites estáticos não listam pastas; evita suposições em runtime.
- **Canonical `https://telafacil.com.br/`:** produção; referência correta para SEO e compartilhamento.
- **Ano no rodapé:** JavaScript (`new Date().getFullYear()`) respeita o pedido de não fixar 2025; alinha com 2026 quando aplicável.
- **Inmetro:** apenas frase cautelosa no texto, sem selo gráfico oficial sem documento em assets.
- **jQuery:** uso pontual (se adotado) para manipulação de classes e eventos em menos linhas; mesma lógica poderia ser vanilla.
