/**
 * @Author: Amirhosseinhpv
 * @Date:   2020/10/27 13:21:31
 * @Email:  its@hpv.im
 * @Last modified by:   Amirhosseinhpv
 * @Last modified time: 2021/05/15 20:18:39
 * @License: GPLv2
 * @Copyright: Copyright © 2020 Amirhosseinhpv, All rights reserved.
 */


(function($) {
  $(document).ready(function() {
    var docHeight = $(document).height();
    var windowHeight = $(window).height();
    var tolorace = 0;
	var header_height = $("header").first().height();
	let root = document.documentElement;
	root.style.setProperty('--header-height', header_height + "px");
    var scrollPercent;

    if (_pba.tggleadminmenubar) {
      $("#wpadminbar, html").addClass("pba_toggle_menubar");
      $("#wpadminbar.pba_toggle_menubar #wp-toolbar ul").first().prepend(`<li class="menupop" id="pba-adminbar-toggle"><a class="ab-item" href="#"><span class="ab-icon"></span></a></li>`)
      $(document).on("click tap", "#wp-admin-bar-root-default #pba-adminbar-toggle", function(e) {
        e.preventDefault();
        var me = $(this);
        $("#wpadminbar").toggleClass("pba_toggle_menubar");
        $("html").toggleClass("pba_toggle_menubar");
      });
    }
    if ($(".pba_scroll_progressbar").length) {
      $('.pba_scroll_progressbar').each(function(n, i) {
        $float = "";
        if ("yes" == $(i).data("reverse")) {
          $float = "revert";
        }
        $(i).append($('<div/>').addClass("pba_filler").css({
          "background": $(i).data("fill"),
          "float": $float,
        }));

        $(i).css({
          "background": $(i).data("bg"),
          "height": $(i).data("height")
        });
        if ($(i).is(".pba_sticky_top")) {
          tolorace = parseInt($(i).data("height"));
          $("#wpadminbar").css("top", $(i).data("height"));
          $(".pba_share_buttons.sticky_top").css("top", $(i).data("height"));
        }
      });
      $(window).scroll(function() {
        scrollPercent = $(window).scrollTop() / (docHeight - windowHeight) * 100;
        $(".pba_scroll_progressbar>.pba_filler").width(scrollPercent + '%');

      });
    }
    if (typeof autonumber !== "undefined" && true === autonumber) {
      prepent_auto_numbering();
    }

    tocdivselector = [];

    if ($(".pba_inline_nav").length) {
      tocdivselector.push(".pba_inline_nav");
    }
    if ($(".pba_table_of_content").length) {
      tocdivselector.push(".pba_table_of_content");
    }
    var go2top = false;
    var anchor = false;
    if (typeof headinggototoc !== "undefined" && true === headinggototoc) {
      go2top = true;
    }
    if (typeof headinganchor !== "undefined" && true === headinganchor) {
      anchor = true;
    }
    add_heading_anchors(true, go2top, anchor, tocdivselector.join(","));

    if (typeof hardencontentcopying !== "undefined" && "" !== hardencontentcopying) {
      $(hardencontentcopying).attr({
        onmousedown: "return false",
        onselectstart: "return false",
        oncopy: "return false",
        onpaste: "return false",
        oncut: "return false",
      }).css({
        "-webkit-touch-callout": "none",
        "-webkit-user-select": "none",
        "-khtml-user-select": "none",
        "-moz-user-select": "none",
        "-ms-user-select": "none",
        "user-select": "none",
      });
    }

    $(document).on("click tap", ".pepro_blogging_assistant.pba_inline_nav.responsive", function(e) {
      e.preventDefault();
      var me = $(this);
      me.removeClass("hover").addClass("hover");
    });
    $(document).mouseup(function(e) {
      var container = $(".pepro_blogging_assistant.pba_inline_nav.responsive.hover");
      if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.removeClass("hover");
      }
    });

    $(document).on("click tap",".pba_inline_nav a",function(e){
      setTimeout(function () {
        $(".pba_inline_nav.responsive.hover").removeClass("hover");
      }, 500);
    });

    if ($(window).width() < 1800) {
      $(".pepro_blogging_assistant.pba_inline_nav").addClass("responsive");
    } else {
      $(".pepro_blogging_assistant.pba_inline_nav").removeClass("responsive");
    }

    $(window).resize(function() {
      if ($(window).width() < 1800) {
        $(".pepro_blogging_assistant.pba_inline_nav").addClass("responsive");
      } else {
        $(".pepro_blogging_assistant.pba_inline_nav").removeClass("responsive");
      }
    });

    $(document).on("click tap", ".dashicons.pba_goto_toc", function(e) {
      e.preventDefault();
      $("html, body").animate({
        scrollTop: $(".pba_table_of_content").first().offset().top - $("header").first().height()
      }, "slow");
    });
    $(document).on("click tap", "a.pba_heading_anchor, .pba_inline_nav>a, .pba_table_of_content>a", function(e) {
      e.preventDefault();
      var me = $(this);
      $("html, body").animate({
        scrollTop: $(me.attr("href")).first().offset().top - $("header").first().height()
      }, "slow");
      if (history.pushState) {
        history.pushState(null, null, me.attr("href"));
      } else {
        location.hash = me.attr("href");
      }
      // document.querySelector(me.attr("href")).scrollIntoView({behavior: 'smooth'});
    });

    function prepent_auto_numbering() {
      var segments = [];
      $(_pba.contentWrapper).find(":header").each(function() {
        var level = parseInt(this.nodeName.substring(1), 10);
        if (segments.length == level) {
          // from Hn to another Hn, just increment the last segment
          segments[level - 1]++;
        } else if (segments.length > level) {
          // from Hn to Hn-x, slice off the last x segments, and increment the last of the remaining
          segments = segments.slice(0, level);
          segments[level - 1]++;
        } else if (segments.length < level) {
          // from Hn to Hn+x, (should always be Hn+1, but I'm doing some error checks anyway)
          // add '1' x times.
          for (var i = 0; i < (level - segments.length); i++) {
            segments.push(1);
          }
        }
        $(this).text(segments.join('.') + '. ' + $(this).text());
      });
    }

    function add_heading_anchors(makelist = false, go2top = false, anchor = false, div = ".pba_table_of_content") {
      var segments = [];
      $(_pba.contentWrapper).find(":header").each(function() {
        var level = parseInt(this.nodeName.substring(1), 10);
        if (segments.length == level) {
          // from Hn to another Hn, just increment the last segment
          segments[level - 1]++;
        } else if (segments.length > level) {
          // from Hn to Hn-x, slice off the last x segments, and increment the last of the remaining
          segments = segments.slice(0, level);
          segments[level - 1]++;
        } else if (segments.length < level) {
          // from Hn to Hn+x, (should always be Hn+1, but I'm doing some error checks anyway)
          // add '1' x times.
          for (var i = 0; i < (level - segments.length); i++) {
            segments.push(1);
          }
        }
        var prev_id = $(this).attr("id");
        $(this).addClass("prev_id_" + prev_id + ` level_${level}`);
        $(this).attr("id", "toc_" + segments.join('_'));
        if (makelist) {
          if ("reply-title" == prev_id) return;
          if ("reply-title" == prev_id) return;
          $(div).append(`<a class='prev_id_${prev_id} level_${level}' href='#toc_${segments.join('_')}'>${$(this).text()}</a>`);
        }
        if (go2top) {
          $(this).append(`<span class="pba_goto_toc dashicons dashicons-arrow-up-alt"></span>`);
        }
        if (anchor) {
          $(this).prepend(`<a href="#toc_${segments.join('_')}" class="pba_heading_anchor toc_${segments.join('_')} prev_id_${prev_id} level_${level}"><span class="dashicons dashicons-admin-links"></span></a>`);
        }
      });
    }

  });
})(jQuery);
