/**
 * WSILVA Redes — site estatico. jQuery e Bootstrap vêm do CDN no HTML.
 */
(function () {
  "use strict";

  var yEl = document.getElementById("ws-year");
  if (yEl) yEl.textContent = String(new Date().getFullYear());

  if (typeof jQuery === "undefined") return;
  var $ = jQuery;

  function $navCollapse() {
    return $(".navbar .navbar-collapse");
  }

  $navCollapse()
    .find("a.nav-link, .btn")
    .on("click", function () {
      if ($(".navbar .navbar-toggler:visible").length) {
        $navCollapse().collapse("hide");
      }
    });

  $(document).on("keydown", function (e) {
    if (e.key === "Escape") {
      $navCollapse().filter(".show").collapse("hide");
    }
  });

  $('a[href^="#"]').on("click", function (e) {
    var id = this.getAttribute("href");
    if (!id || id === "#" || id.length < 2) return;
    var $target = $(id);
    if (!$target.length) return;
    e.preventDefault();
    var offset = 80;
    $("html, body").animate({ scrollTop: $target.offset().top - offset }, 400);
  });

  $("#form-orcamento").on("submit", function (e) {
    e.preventDefault();
    var $f = $(this);
    $f.find(".is-invalid").removeClass("is-invalid");
    $f.find(".ws-form-error").removeClass("show").text("");

    var nome = ($f.find('[name="nome"]').val() || "").trim();
    var cidade = ($f.find('[name="cidade"]').val() || "").trim();
    var tipo = $f.find('[name="tipo_imovel"]').val();
    var sac = parseInt($f.find('[name="qtd_sacada"]').val(), 10);
    var jan = parseInt($f.find('[name="qtd_janelas"]').val(), 10);
    var obs = ($f.find('[name="observacoes"]').val() || "").trim();

    if (isNaN(sac) || sac < 0) sac = 0;
    if (isNaN(jan) || jan < 0) jan = 0;

    var err = false;
    if (!nome) { $f.find('[name="nome"]').addClass("is-invalid"); err = true; }
    if (!cidade) { $f.find('[name="cidade"]').addClass("is-invalid"); err = true; }
    if (!tipo) { $f.find('[name="tipo_imovel"]').addClass("is-invalid"); err = true; }
    if (sac === 0 && jan === 0) {
      $f.find("#orcamento-error-qty").addClass("show")
        .text("Indique a quantidade de sacadas e/ou janelas (pelo menos uma deve ser maior que zero).");
      err = true;
    }
    if (err) return;

    var lines = [];
    lines.push("Olá, WSILVA Redes! Gostaria de solicitar um orçamento.");
    lines.push("");
    lines.push("Nome: " + nome);
    lines.push("Cidade/Bairro: " + cidade);
    lines.push("");
    lines.push("Tipo do imóvel: " + tipo);
    lines.push("");
    lines.push("Locais de instalação:");
    if (sac > 0) lines.push("- Sacada: " + sac);
    if (jan > 0) lines.push("- Janelas: " + jan);
    if (obs) {
      lines.push("");
      lines.push("Observações:");
      lines.push(obs);
    }
    lines.push("");
    lines.push("Aguardo o retorno. Obrigado!");
    var url = "https://wa.me/5511991237392?text=" + encodeURIComponent(lines.join("\n"));
    window.open(url, "_blank", "noopener,noreferrer");
  });

  var $g = $(".ws-gallery-scroller");
  if ($g.length) {
    var step = function () {
      var w = $g.find(".ws-gallery-item").first().outerWidth(true) || 280;
      return Math.min($g.outerWidth() * 0.85, w + 16);
    };
    $("#ws-gallery-prev").on("click", function () { $g[0].scrollBy({ left: -step(), behavior: "smooth" }); });
    $("#ws-gallery-next").on("click", function () { $g[0].scrollBy({ left: step(), behavior: "smooth" }); });
  }
})();