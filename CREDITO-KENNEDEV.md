# Crédito "Desenvolvido por Kennedev" — padrão para todos os sites

Snippet reutilizável do selo de autoria no rodapé. Use **igual** em todo site novo
para manter o padrão. Referência viva: `vidrosplanejados/` (implementação atual).

---

## 1. Logo (asset)

Fonte oficial em WebP (fundo escuro embutido, quadrado 1024×1024):

```
assets/logo-kennedev/logo-kennedev.webp
```

**Copie** esse arquivo para dentro dos assets do site novo (cada site é publicado
isolado — nunca referencie caminho externo tipo `../../assets/...`). Ex.:

```
<seu-site>/assets/img/logo-kennedev.webp
```

Não precisa exportar em alta: no rodapé ele aparece com ~26px. Uma cópia de 256px
(~3 KB) é suficiente.

---

## 2. HTML

Cole no rodapé, na barra inferior (junto do copyright):

```html
<a class="footer-credit" href="https://www.kennedev.com.br" target="_blank" rel="noopener">
  <span>Desenvolvido por</span>
  <img src="assets/img/logo-kennedev.webp" alt="Kennedev" width="26" height="26" loading="lazy" />
  <strong>Kennedev</strong>
</a>
```

> Ajuste apenas o `src` para o caminho do logo no site em questão.

---

## 3. CSS (auto-contido)

As cores usam `var(--…)` com fallback embutido, então funciona em qualquer site
mesmo sem as variáveis definidas. Rodapé **claro** (padrão):

```css
.footer-credit { display: inline-flex; align-items: center; gap: .5rem; color: var(--muted, #56637a); font-size: .85rem; }
.footer-credit img { width: 26px; height: 26px; border-radius: 7px; display: block; }
.footer-credit strong { color: var(--ink, #0f1a2d); font-weight: 700; }
.footer-credit:hover strong { color: var(--blue-ink, #144bd6); }
```

### Rodapé escuro

O logo tem fundo escuro embutido, então em rodapé escuro o selo se funde com o fundo.
Troque as cores do texto para claras:

```css
.footer-credit { display: inline-flex; align-items: center; gap: .5rem; color: rgba(255,255,255,.6); font-size: .85rem; }
.footer-credit img { width: 26px; height: 26px; border-radius: 7px; display: block; }
.footer-credit strong { color: #fff; font-weight: 700; }
.footer-credit:hover strong { color: #4fd0ff; }
```

---

## 4. Layout da barra inferior (opcional)

Para copyright à esquerda e crédito à direita, com empilhamento no mobile:

```css
.footer-bottom {
  display: flex; flex-wrap: wrap; gap: .4rem 1.5rem;
  justify-content: space-between; align-items: center;
}
```

```html
<div class="footer-bottom">
  <span>&copy; 2026 Nome do Cliente</span>
  <!-- snippet .footer-credit aqui -->
</div>
```

---

## 5. Sites PHP (padrão legado, sem logo)

Os sites PHP (`imobiliaria/`, `vicente/`, `casaquadradaimobiliaria/`) usam a versão
só-texto no helper `lib/publico.php`. Mantida para compatibilidade:

```php
echo '<span>Desenvolvido por <a href="https://www.kennedev.com.br" target="_blank" rel="noopener">Kennedev</a></span>';
```

Para adicionar o logo nesses, use o HTML do item 2 dentro do `echo`.

---

## Checklist ao criar site novo

- [ ] Copiar `logo-kennedev.webp` para os assets do site
- [ ] Colar o HTML do item 2 (ajustar `src`)
- [ ] Colar o CSS do item 3 (claro ou escuro conforme o rodapé)
- [ ] Link sempre `https://www.kennedev.com.br` + `target="_blank"` + `rel="noopener"`
