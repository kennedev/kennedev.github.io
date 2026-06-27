/**
 * Tecnopest Controle de Pragas — site estático (vanilla JS, sem dependências).
 */
(function () {
  "use strict";

  // TODO: substituir pelo WhatsApp real (DDI + DDD + número, só dígitos). Ex.: 5511999999999
  var WHATSAPP = "55XXXXXXXXXXX";

  /* Ano dinâmico no footer */
  var anoEl = document.getElementById("ano");
  if (anoEl) anoEl.textContent = String(new Date().getFullYear());

  /* Menu mobile */
  var toggle = document.getElementById("navToggle");
  var menu = document.getElementById("navMenu");

  function closeMenu() {
    if (!menu || !toggle) return;
    menu.classList.remove("open");
    toggle.setAttribute("aria-expanded", "false");
    document.body.classList.remove("nav-open");
  }

  if (toggle && menu) {
    toggle.addEventListener("click", function () {
      var open = menu.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      document.body.classList.toggle("nav-open", open);
    });
    menu.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", closeMenu);
    });
  }

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeMenu();
  });

  /* Scroll suave com offset da navbar */
  var navH = 72;
  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener("click", function (e) {
      var id = link.getAttribute("href");
      if (!id || id === "#" || id.length < 2) return;
      var target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      var y = target.getBoundingClientRect().top + window.pageYOffset - navH;
      window.scrollTo({ top: y, behavior: "smooth" });
    });
  });

  /* Formulário → WhatsApp */
  var form = document.getElementById("formAgendamento");
  if (form) {
    var errEl = document.getElementById("formError");

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (errEl) errEl.textContent = "";
      form.querySelectorAll(".invalid").forEach(function (el) { el.classList.remove("invalid"); });

      function val(name) {
        var el = form.elements[name];
        return el ? String(el.value || "").trim() : "";
      }

      var nome = val("nome");
      var telefone = val("telefone");
      var imovel = val("tipo_imovel");
      var servico = val("servico");
      var mensagem = val("mensagem");

      var faltando = [];
      [["nome", nome], ["telefone", telefone], ["tipo_imovel", imovel], ["servico", servico]]
        .forEach(function (pair) {
          if (!pair[1] && form.elements[pair[0]]) {
            form.elements[pair[0]].classList.add("invalid");
            faltando.push(pair[0]);
          }
        });

      if (faltando.length) {
        if (errEl) errEl.textContent = "Preencha os campos obrigatórios para continuar.";
        return;
      }

      var linhas = [
        "Olá, Tecnopest! Gostaria de agendar uma visita.",
        "",
        "Nome: " + nome,
        "Telefone: " + telefone,
        "Tipo de imóvel: " + imovel,
        "Serviço: " + servico
      ];
      if (mensagem) {
        linhas.push("", "Mensagem:", mensagem);
      }
      linhas.push("", "Aguardo o retorno. Obrigado!");

      var url = "https://wa.me/" + WHATSAPP + "?text=" + encodeURIComponent(linhas.join("\n"));
      window.open(url, "_blank", "noopener,noreferrer");
    });
  }
})();
