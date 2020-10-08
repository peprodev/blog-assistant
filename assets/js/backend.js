(function($) {
  $(document).ready(function() {
    $(document).on("click tap", ".tab-heading>a", function(e) {
      e.preventDefault();
      $(".tab-heading>a").removeClass("active");
      $(this).addClass("active");
      $("input#" + $(this).find("label").attr("for")).prop("checked", true);
    });
    // $(document).on("click tap", ".toggle-btn", function(e) {
    //   e.preventDefault();
    //   $(".toggle-content").hide();
    //   $(".toggle-btn.active").removeClass("active");
    //   $(this).next(".toggle-content").toggle();
    //   $(this).toggleClass("active");
    // });
    if ($(".tab-heading>a").length) {
      $(".tab-heading>a").first().click();
    }
    // if ($(".cpt-options>h3").length) {
    //   $(".cpt-options>h3").first().click();
    // }
    $(".tab-content>div.cpt-options.cpt--post").append($("<div/>").addClass("tab-heading"));

    function scroll(e, of = 0) {
      $('html, body').animate({
        scrollTop: e.offset().top - of
      }, 500);
    }
    $("input[type='text'].wpcolorpicker").wpColorPicker();
    $("input[type='text']").each(function(n,i) {
      $(i).val($(i).attr("value")).trigger("change");
    });
  });
})(jQuery);
