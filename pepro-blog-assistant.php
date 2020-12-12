<?php
/*
Plugin Name: Pepro Blogging Assistant
Description: Pepro Blogging Assistant gives you Table of Content, Sharing Buttons, Heading navigations, Disallow Content Copying and a bunch of other tools to enhance user readability and the site's UX
Contributors: amirhosseinhpv, peprodev
Tags: blog, blogging assistant, blogging tools, table of content, share buttons, navigation
Author: Pepro Dev. Group
Author URI: https://pepro.dev/
Developer: Amirhosseinhpv
Developer URI: https://hpv.im/
Plugin URI: https://pepro.dev/blogging-assistant/
Version: 1.3.0
Stable tag: 1.3.0
Requires at least: 5.0
Tested up to: 5.6
Requires PHP: 5.6
Text Domain: pepro-blogging-assistant
Domain Path: /languages
Copyright: (c) 2020 Pepro Dev. Group, All rights reserved.
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
# @Last modified by:   Amirhosseinhpv
# @Last modified time: 2020/12/12 13:10:32

defined("ABSPATH") or die("Pepro Blogging Assistant :: Unauthorized Access!");

if (!class_exists("peproBloggingAssistant")) {
  class peproBloggingAssistant
  {
    private static $_instance = null;
    public $td;
    public $url;
    public $version;
    public $title;
    public $title_w;
    public $db_slug;
    private $plugin_dir;
    private $plugin_url;
    private $assets_url;
    private $plugin_basename;
    private $plugin_file;
    private $deactivateURI;
    private $deactivateICON;
    private $versionICON;
    private $authorICON;
    private $settingICON;
    private $db_table = null;
    private $manage_links = array();
    private $meta_links = array();
    public function __construct()
    {
      global $wpdb;
      $this->td = "pepro-blogging-assistant";
      self::$_instance = $this;
      $this->db_slug = $this->td;
      $this->db_table = $wpdb->prefix . $this->db_slug;
      $this->plugin_dir = plugin_dir_path(__FILE__);
      $this->plugin_url = plugins_url("", __FILE__);
      $this->assets_url = plugins_url("/assets/", __FILE__);
      $this->plugin_basename = plugin_basename(__FILE__);
      $this->url = admin_url("options-general.php?page={$this->db_slug}");
      $this->plugin_file = __FILE__;
      $this->version = "1.3.0";
      $this->deactivateURI = null;
      $this->deactivateICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-dismiss" aria-hidden="true"></span> ';
      $this->versionICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-plugins" aria-hidden="true"></span> ';
      $this->authorICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-users" aria-hidden="true"></span> ';
      $this->settingURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-admin-settings dashicons-small" aria-hidden="true"></span> ';
      $this->submitionURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-images-alt dashicons-small" aria-hidden="true"></span> ';
      $this->title = __("Blogging Assistant", $this->td);
      $this->title_w = sprintf(__("%2\$s ver. %1\$s", $this->td), $this->version, $this->title);
      add_action("init", array($this, 'init_plugin'));
      add_filter("the_content", array($this, 'filter_the_content'), -1 );
      // add_filter("pepro_blog_assistant/debug_frontend_sections", function($aa,$bb){return $bb;}, 10, 2);

    }
    public function init_plugin()
    {
      add_filter("plugin_action_links_{$this->plugin_basename}", array($this, 'plugins_row_links'));
      add_action("plugin_row_meta", array( $this, 'plugin_row_meta' ), 10, 2);
      add_action("admin_menu", array($this, 'admin_menu'));
      add_action("admin_init", array($this, 'admin_init'));
      add_action("admin_enqueue_scripts", array($this, 'admin_enqueue_scripts'));
      add_action("wp_enqueue_scripts", array($this, 'wp_enqueue_scripts'));
    }
    public function filter_the_content( $content )
    {
      if ( ( is_single() || is_page() ) && in_the_loop() && is_main_query() ) {
        $current_post = get_post(); $slug = $current_post->post_type; $contentEr = $content_before = $content_after = "";
        foreach ($this->get_assistants() as $section_id => $section_data) {
          foreach ($section_data["opts"] as $key => $value) {
            $currentValue_org = $this->read_opt("pepro-blogging-assistantconfig");

            if (!isset($currentValue_org[$slug]))
              continue;
            if (!isset($currentValue_org[$slug][$section_id]))
              continue;
            if (!isset($currentValue_org[$slug][$section_id][$value["id"]]))
              continue;
            if (!isset($currentValue_org[$slug][$section_id][$value["id"]."_opt"]))
              continue;

            $currentValue = $currentValue_org[$slug][$section_id][$value["id"]];
            $currentValue2nd = $currentValue_org[$slug][$section_id][$value["id"]."_opt"];
            $setting = array();
            if (!empty($value["opts"])) foreach ($value["opts"] as $id => $opt) {$setting[$id] = $currentValue2nd[$id];}
            $contentEr .= "<pre dir='ltr' style='background: #e1f1fa; border:1px solid gray'>". print_r(["id" => $value["id"], "value" => $currentValue, "config" => $setting],1) ."</pre>";
            $content_assist = $this->assist_with_hook($value["id"], $currentValue, $setting);
            $content_before .= $content_assist["before"];
            $content_after .= $content_assist["after"];
          }
        }
        $contentEr = apply_filters( "pepro_blog_assistant/debug_frontend_sections", "", $contentEr);
        $content = apply_filters( "pepro_blog_assistant/return_content", "{$contentEr}{$content_before}{$content}{$content_after}", $content, $content_before, $content_after, $contentEr) ;
      }
      return $content;
    }
    public function assist_with_hook($key, $val, $setting)
    {
      do_action( "pepro_blog_assistant/run_before_setting_action", $key, $val, $setting );
      if ("yes" !== $val) return;
      switch ($key) {
        case 'prog_top':
        case 'prog_bottom':
          $setting["prgs_color"]  = empty($setting["prgs_color"]) ? "#1c8140" : $setting["prgs_color"];
          $setting["bg_color"]    = empty($setting["bg_color"]) ? "#e2e3e2" : $setting["bg_color"];
          $setting["height"]      = empty($setting["height"]) ? "2px" : $setting["height"];
          $setting["reverse"]     = empty($setting["reverse"]) ? "no" : $setting["reverse"];
          $data = array(
            "before" => "",
            "after" => "<div class='pepro_blogging_assistant pba_scroll_progressbar ".( "prog_top" == $key ? "pba_sticky_top" : "pba_sticky_bottom")."' data-reverse='{$setting["reverse"]}' data-height='{$setting["height"]}' data-fill='{$setting["prgs_color"]}' data-bg='{$setting["bg_color"]}'></div>",
          );
          break;
        case 'toc_before':
        case 'toc_after':
          $data = array(
            "before" => ("toc_before" == $key ? "<div class='pepro_blogging_assistant pba_table_of_content toc_before_content'></div>" : ""),
            "after" => ("toc_after" == $key ? "<div class='pepro_blogging_assistant pba_table_of_content toc_after_content'></div>" : ""),
          );
          break;
        case 'nav_right':
        case 'nav_left':
          $setting["hover_color"] = empty($setting["hover_color"]) ? "#145ecf" : $setting["hover_color"];
          $data = array(
            "before" => ("nav_right" == $key ? "<div class='pepro_blogging_assistant pba_inline_nav pba_sticky_right nav_right'></div>" : "<div class='pepro_blogging_assistant pba_inline_nav pba_sticky_left nav_left'></div>"),
            "after" => "<style> .pepro_blogging_assistant.pba_inline_nav.{$key} a:hover { border-right-color: {$setting["hover_color"]} !important; }</style>",
          );
          break;
        case 'autonumber':
          $data = array(
            "before" => "",
            "after" => "<script>autonumber = true;</script>",
          );
          break;
        case 'headinggototoc':
          $data = array(
            "before" => "",
            "after" => "<script>headinggototoc = true;</script>",
          );
          break;
        case 'headinganchor':
          $data = array(
            "before" => "",
            "after" => "<script>headinganchor = true;</script>",
          );
          break;
        case 'content_highlight':
          $setting["content_selection_color"] = empty($setting["content_selection_color"]) ? "" : $setting["content_selection_color"];
          $setting["content_selection_bgcolor"] = empty($setting["content_selection_bgcolor"]) ? "" : $setting["content_selection_bgcolor"];
          $data = array(
            "before" => "",
            "after" => "<style>
            p::-moz-selection { color: {$setting["content_selection_color"]}}p::selection { color: {$setting["content_selection_color"]}; }
            ::-moz-selection { background: {$setting["content_selection_bgcolor"]}; }::selection { background: {$setting["content_selection_bgcolor"]}; }</style>",
          );
          break;
        case 'lock_content_copy':
          $setting["protect_selector"] = empty($setting["protect_selector"]) ? "body" : $setting["protect_selector"];
          $data = array(
            "before" => "",
            "after" => "<script>hardencontentcopying = \"{$setting["protect_selector"]}\";</script>",
          );
          break;
        case 'share_top':
        case 'share_bottom':
        case 'sticky_top':
        case 'sticky_right':
        case 'sticky_bottom':
        case 'sticky_left':
          $setting["share_text"] = empty($setting["share_text"]) ? "" : $setting["share_text"];
          $setting["share_print"] = empty($setting["share_print"]) ? "" : $setting["share_print"];
          $setting["share_email"] = empty($setting["share_email"]) ? "" : $setting["share_email"];
          $setting["share_facebook"] = empty($setting["share_facebook"]) ? "" : $setting["share_facebook"];
          $setting["share_linkedin"] = empty($setting["share_linkedin"]) ? "" : $setting["share_linkedin"];
          $setting["share_twitter"] = empty($setting["share_twitter"]) ? "" : $setting["share_twitter"];
          $setting["share_telegram"] = empty($setting["share_telegram"]) ? "" : $setting["share_telegram"];
          $setting["share_whatsapp"] = empty($setting["share_whatsapp"]) ? "" : $setting["share_whatsapp"];
          $setting["share_googleplus"] = empty($setting["share_googleplus"]) ? "" : $setting["share_googleplus"];
          $setting["share_tumblr"] = empty($setting["share_tumblr"]) ? "" : $setting["share_tumblr"];
          $setting["share_pinterest"] = empty($setting["share_pinterest"]) ? "" : $setting["share_pinterest"];

          $setting["color"] = empty($setting["color"]) ? "" : $setting["color"];
          $setting["hover"] = empty($setting["hover"]) ? "" : $setting["hover"];
          $setting["radius"] = empty($setting["radius"]) ? "" : $setting["radius"];
          $setting["bgcolor"] = empty($setting["bgcolor"]) ? "" : $setting["bgcolor"];
          $setting["bghover"] = empty($setting["bghover"]) ? "" : $setting["bghover"];

          $buttons = "";
          $url = get_the_permalink();
          $title = get_the_title();
          $description = get_the_title() . " — " . get_bloginfo('name');

          // https://github.com/bradvin/social-share-urls
          $buttons .= (!empty($setting["share_text"]))        ? "<span>{$setting["share_text"]}</span>" : "";
          $buttons .= ("yes" == $setting["share_print"])      ? "<a href='javascript:window.print();' class='pba_share pba_btn_share'><i class='fas fa-print'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_email"])      ? "<a target='_blank' href='mailto:?subject={$description}&body={$description} {$url}' class='pba_share pba_btn_share'><i class='fas fa-envelope'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_facebook"])   ? "<a target='_blank' href='https://www.facebook.com/sharer.php?u={$url}' class='pba_share pba_btn_share'><i class='fab fa-facebook'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_linkedin"])   ? "<a target='_blank' href='https://www.linkedin.com/sharing/share-offsite/?url={$url}' class='pba_share pba_btn_share'><i class='fab fa-linkedin'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_twitter"])    ? "<a target='_blank' href='https://twitter.com/intent/tweet?original_referer={$url}&url={$url}&text={$description}' class='pba_share pba_btn_share'><i class='fab fa-twitter'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_googleplus"]) ? "<a target='_blank' href='https://plus.google.com/share?url={$url}' class='pba_share pba_btn_share'><i class='fab fa-google-plus-g'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_tumblr"])     ? "<a target='_blank' href='https://www.tumblr.com/share/link?name={$description} {$url}&url={$url}' class='pba_share pba_btn_share'><i class='fab fa-tumblr'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_pinterest"])  ? "<a target='_blank' href='https://pinterest.com/pin/create/button/?url={$url}&description={$description} {$url}' class='pba_share pba_btn_share'><i class='fab fa-pinterest-p'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_telegram"])   ? "<a target='_blank' href='https://t.me/share/url?url={$url}&text={$description}' class='pba_share pba_btn_share'><i class='fab fa-telegram'></i></a>" : "";
          $buttons .= ("yes" == $setting["share_whatsapp"])   ? "<a target='_blank' href='https://wa.me/?text={$description} {$url}' class='pba_share pba_btn_share'><i class='fab fa-whatsapp'></i></a>" : "";

          $csss = "<style>
          .{$key} a.pba_share:hover,
          .{$key} a.pba_share:focus{background: {$setting["bghover"]}; color: {$setting["hover"]};}
          .{$key} a.pba_share {background: {$setting["bgcolor"]}; color: {$setting["color"]}; border-radius: {$setting["radius"]};}
          </style>";

          $data = array(
            "before" => (in_array($key, array("share_top","sticky_top","sticky_right"))) ? "$csss<div class='pepro_blogging_assistant pba_share_buttons $key'>$buttons</div>" : "",
            "after" => (in_array($key, array("share_bottom","sticky_bottom","sticky_left"))) ? "$csss<div class='pepro_blogging_assistant pba_share_buttons $key'>$buttons</div>" : "",
          );
          break;

        default:
          $data = array(
            "before" => "",
            "after" => ""
          );
          break;
      }
      do_action( "pepro_blog_assistant/run_after_setting_action", $key, $val, $setting, $data );

      return apply_filters( "pepro_blog_assistant/return_setting_action", $data, $key, $val, $setting );
    }
    public function get_assistants()
    {
      $cpt_extra_tools = apply_filters("pepro_blog_assistant/get_assistant/share", array(
          "color" => array(
              "id" => "color",
              "class" => "wpcolorpicker",
              "default" => "",
              "label" => __("Icon Color", $this->td)
          ) ,
          "hover" => array(
              "id" => "hover",
              "class" => "wpcolorpicker",
              "default" => "",
              "label" => __("Hover Color", $this->td)
          ) ,
          "bgcolor" => array(
              "id" => "bgcolor",
              "class" => "wpcolorpicker",
              "default" => "",
              "label" => __("Background Color", $this->td)
          ) ,
          "bghover" => array(
              "id" => "bghover",
              "class" => "wpcolorpicker",
              "default" => "",
              "label" => __("Hover Background Color", $this->td)
          ) ,
          "radius" => array(
              "id" => "radius",
              "default" => "2px",
              "label" => __("Border Radius", $this->td)
          ) ,
          "share_print" => array(
              "id" => "share_print",
              "type" => "checkbox",
              "label" => __("Print", $this->td)
          ) ,
          "share_email" => array(
              "id" => "share_email",
              "type" => "checkbox",
              "label" => __("Share on Email", $this->td)
          ) ,
          "share_facebook" => array(
              "id" => "share_facebook",
              "type" => "checkbox",
              "label" => __("Share on Facebook", $this->td)
          ) ,
          "share_linkedin" => array(
              "id" => "share_linkedin",
              "type" => "checkbox",
              "label" => __("Share on LinkedIn", $this->td)
          ) ,
          "share_twitter" => array(
              "id" => "share_twitter",
              "type" => "checkbox",
              "label" => __("Share on Twitter", $this->td)
          ) ,
          "share_googleplus" => array(
              "id" => "share_googleplus",
              "type" => "checkbox",
              "label" => __("Share on Google-plus", $this->td)
          ) ,
          "share_tumblr" => array(
              "id" => "share_tumblr",
              "type" => "checkbox",
              "label" => __("Share on Tumblr", $this->td)
          ) ,
          "share_pinterest" => array(
              "id" => "share_pinterest",
              "type" => "checkbox",
              "label" => __("Share on Pinterest", $this->td)
          ) ,


          "share_telegram" => array(
              "id" => "share_telegram",
              "type" => "checkbox",
              "label" => __("Share on Telegram", $this->td)
          ) ,
          "share_whatsapp" => array(
              "id" => "share_whatsapp",
              "type" => "checkbox",
              "label" => __("Share on WhatsApp", $this->td)
          ) ,
      ));
      $cpt_extra_tools2 = apply_filters("pepro_blog_assistant/get_assistant/share",
        array_merge(
          array(
            "share_text" => array(
                            "id"      => "share_text",
                            "label"   => __("Share Text", $this->td),
                            "default" => __("Share this article: ",$this->td),
                          )
          ),
          $cpt_extra_tools
      ));
      $cpt_toc_tools = apply_filters("pepro_blog_assistant/get_assistant/toc", array(
          array(
              "id" => "toc_before",
              "label" => __("TOC Before Content", $this->td),
          ) ,
          array(
              "id" => "toc_after",
              "label" => __("TOC After Content", $this->td),
          ) ,
          array(
              "id" => "autonumber",
              "label" => __("Auto-prepend heading number", $this->td),
          ) ,
          array(
              "id" => "headinggototoc",
              "label" => __("Append go to TOC to Heading", $this->td),
          ) ,
          array(
              "id" => "headinganchor",
              "label" => __("Prepend Anchor link to Heading", $this->td),
          ) ,
      ));
      $cpt_nav_tools = apply_filters("pepro_blog_assistant/get_assistant/nav", array(
          array(
              "id" => "nav_right",
              "label" => __("Navigation on Right", $this->td),
              "opts" => array(
                  "hover_color" => array(
                      "label" => __("Menu-item hover border", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#145ecf",
                  ) ,
              ) ,
          ) ,
          array(
              "id" => "nav_left",
              "label" => __("Navigation on Left", $this->td),
              "opts" => array(
                  "hover_color" => array(
                      "label" => __("Menu-item hover border", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#145ecf",
                  ) ,
              ) ,
          ) ,
      ));
      $cpt_prgoressbar_tools = apply_filters("pepro_blog_assistant/get_assistant/progressbar", array(
          array(
              "id" => "prog_top",
              "label" => __("Top Progressbar", $this->td) ,
              "opts" => array(
                  "prgs_color" => array(
                      "label" => __("Progress Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#1c8140",
                  ) ,
                  "bg_color" => array(
                      "label" => __("Background Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#e2e3e2",
                  ) ,
                  "reverse" => array(
                      "label" => __("Reverse Direction", $this->td) ,
                      "class" => "",
                      "type" => "checkbox",
                      "attr" => "",
                      "default" => "",
                  ) ,
                  "height" => array(
                      "label" => __("Height", $this->td) ,
                      "class" => "",
                      "attr" => "",
                      "default" => "2px",
                  ) ,
              ) ,
          ) ,
          array(
              "id" => "prog_bottom",
              "label" => __("Bottom Progressbar", $this->td) ,
              "opts" => array(
                  "prgs_color" => array(
                      "label" => __("Progress Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#1c8140",
                  ) ,
                  "bg_color" => array(
                      "label" => __("Background Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "#e2e3e2",
                  ) ,
                  "reverse" => array(
                      "label" => __("Reverse Direction", $this->td) ,
                      "class" => "",
                      "type" => "checkbox",
                      "attr" => "",
                      "default" => "",
                  ) ,
                  "height" => array(
                      "label" => __("Height", $this->td) ,
                      "class" => "",
                      "attr" => "",
                      "default" => "2px",
                  ) ,
              ) ,
          ) ,
      ));
      $cpt_advanced_tools = apply_filters("pepro_blog_assistant/get_assistant/advanced", array(
          array(
              "id" => "lock_content_copy",
              "label" => __("Protect Content from being copying", $this->td) ,
              "opts" => array(
                  "protect_selector" => array(
                      "label" => __("Content Container Selector", $this->td) ,
                      "class" => "",
                      "attr" => " dir=ltr ",
                      "default" => "body",
                  ) ,
              ) ,
          ) ,
          array(
              "id" => "content_highlight",
              "label" => __("Content Highlight Color", $this->td) ,
              "opts" => array(
                  "content_selection_color" => array(
                      "label" => __("Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "",
                  ) ,
                  "content_selection_bgcolor" => array(
                      "label" => __("Background Color", $this->td) ,
                      "class" => "wpcolorpicker",
                      "attr" => "",
                      "default" => "",
                  ) ,
              ) ,
          ) ,
      ));
      $sections = apply_filters("pepro_blog_assistant/get_assistants", array(
          "share_buttons" => array(
            "label" => __("Share buttons", $this->td) ,
            "opts" => array(
              "share_top" => array(
                "id" => "share_top",
                "label" => __("Share-widget Before Content", $this->td) ,
                "opts" => $cpt_extra_tools2
              ) ,
              "share_bottom" => array(
                "id" => "share_bottom",
                "label" => __("Share-widget After Content", $this->td) ,
                "opts" => $cpt_extra_tools2
              ) ,
              "sticky_top" => array(
                "id" => "sticky_top",
                "label" => __("Share-widget Float-top", $this->td) ,
                "opts" => $cpt_extra_tools2
              ) ,
              "sticky_bottom" => array(
                "id" => "sticky_bottom",
                "label" => __("Share-widget Float-bottom", $this->td) ,
                "opts" => $cpt_extra_tools2
              ) ,
              "sticky_right" => array(
                "id" => "sticky_right",
                "label" => __("Share-widget Float-right", $this->td) ,
                "opts" => $cpt_extra_tools
              ) ,
              "sticky_left" => array(
                "id" => "sticky_left",
                "label" => __("Share-widget Float-left", $this->td) ,
                "opts" => $cpt_extra_tools
              ) ,
            ) ,
          ) ,
          "table_of_content" => array(
              "label" => __("Table of Content", $this->td) ,
              "opts" => $cpt_toc_tools
          ) ,
          "inpage_nav" => array(
              "label" => __("In-page Navigation", $this->td) ,
              "opts" => $cpt_nav_tools
          ) ,
          "scroll_progressbar" => array(
              "label" => __("Scroll progressbar", $this->td) ,
              "opts" => $cpt_prgoressbar_tools
          ) ,
          "advanced" => array(
              "label" => __("Advanced Options", $this->td) ,
              "opts" => $cpt_advanced_tools
          ) ,
      ));
      return $sections;
    }
    public function get_setting_options()
    {
      return array(
        array(
          "name" => "{$this->db_slug}_general",
          "data" => array(
            "{$this->db_slug}-clearunistall" => "no",
            "{$this->db_slug}-addfa" => "no",
            "{$this->db_slug}-tggleadminmenubar" => "no",
            "{$this->db_slug}-content-wrapper" => ".entry-content",
            "pepro-blogging-assistantconfig" => "",
          )
        ),
      );
    }
    public function get_meta_links()
    {
      if (!empty($this->meta_links)) {return $this->meta_links;
      }
      $this->meta_links = array(
        'support'      => array(
          'title'       => __('Support', $this->td),
          'description' => __('Support', $this->td),
          'icon'        => 'dashicons-admin-site',
          'target'      => '_blank',
          'url'         => "mailto:support@pepro.dev?subject={$this->title}",
        ),
      );
      return $this->meta_links;
    }
    public function get_manage_links()
    {
      if (!empty($this->manage_links)) {return $this->manage_links; }
      $this->manage_links = array( $this->settingURL . __("Settings", $this->td) => $this->url, );
      return $this->manage_links;
    }
    public static function uninstall_hook()
    {
      $ppa = new peproBloggingAssistant;
      if (get_option("{$ppa->db_slug}-clearunistall", "no") === "yes") {
        $peproBloggingAssistant_class_options = $ppa->get_setting_options();
        foreach ($peproBloggingAssistant_class_options as $options) {
          $opparent = $options["name"];
          foreach ($options["data"] as $optname => $optvalue) {
            unregister_setting($opparent, $optname);
            delete_option($optname);
          }
        }
      }
    }
    protected function update_footer_info()
    {
      $f = "pepro_temp_stylesheet.".current_time("timestamp");
      wp_register_style($f, null);
      wp_add_inline_style($f," #footer-left b a::before { content: ''; background: url('{$this->assets_url}/images/peprodev.svg') no-repeat; background-position-x: center; background-position-y: center; background-size: contain; width: 60px; height: 40px; display: inline-block; pointer-events: none; position: absolute; -webkit-margin-before: calc(-60px + 1rem); margin-block-start: calc(-60px + 1rem); -webkit-filter: opacity(0.0);
      filter: opacity(0.0); transition: all 0.3s ease-in-out; }#footer-left b a:hover::before { -webkit-filter: opacity(1.0); filter: opacity(1.0); transition: all 0.3s ease-in-out; }[dir=rtl] #footer-left b a::before {margin-inline-start: calc(30px);}");
      wp_enqueue_style($f);
      add_filter( 'admin_footer_text', function () {
        return sprintf(
          _x("Thanks for using %s products", "footer-copyright", $this->td), "<b><a href='https://pepro.dev/' target='_blank' >".__("Pepro Dev", $this->td)."</a></b>"
          );
        },
        11000
      );
      add_filter( 'update_footer', function () { return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version); }, 1100 );
    }
    public function admin_menu()
    {
      add_options_page( $this->title_w, $this->title, "manage_options", $this->db_slug, array($this,'help_container'));
      // add_menu_page(
      //   $this->title_w,
      //   $this->title,
      //   "manage_options",
      //   $this->db_slug,
      //   array($this,'help_container'),
      //   "{$this->assets_url}/images/icon.png",
      //   81
      // );
    }
    public function print_opts($opt_array, $name, $val)
    {
      if (!$opt_array || empty($opt_array)) return;
      $output = "";
      foreach ($opt_array as $id => $opt) {

        $opt["type"] = (!isset($opt["type"]) || empty($opt["type"])) ? "input" : $opt["type"];
        $opt["default"] = (!isset($opt["default"]) || empty($opt["default"])) ? "" : $opt["default"];
        $opt["attr"] = (!isset($opt["attr"]) || empty($opt["attr"])) ? "" : $opt["attr"];

        $el_name = esc_attr("{$name}[$id]");
        $el_value = esc_attr((!isset($val[$id]) || empty($val[$id])) ? $opt["default"] : $val[$id]);

        switch ($opt["type"]) {
          case 'input':
            $type = esc_attr($opt["input_type"]) ?: "text";
            $attr = esc_attr($opt["attr"]) ?: "";
            $class = esc_attr($opt["class"]) ?: "";
            $label = esc_attr($opt["label"]) ?: "";

            $output .= "<p class='setting-level-3 input-field'><label class='setting-label-3' for='$el_name'>$label</label><input id='$el_name' class='$class' type='$type' $attr name=\"$el_name\" value=\"$el_value\" /></p>";
          break;
          case 'checkbox':
            $attr = esc_attr($opt["attr"]) ?: "";
            $class = esc_attr($opt["class"]) ?: "";
            $label = esc_attr($opt["label"]) ?: "";
            $output .= "<p class='setting-level-3 checkbox-field'>
            <label class='setting-label-3' for='$el_name'><input id='$el_name' class='$class' type='checkbox' ". checked( $el_value, "yes", false )." $attr name=\"$el_name\" value='yes' /> $label</label></p>";
          break;

          default:
          // code...
          break;
        }

      }
      return $output;
    }
    public function help_container($hook)
    {
      ob_start();
      $this->update_footer_info();
      $input_number = ' dir="ltr" lang="en-US" min="0" step="1" ';
      $input_english = ' dir="ltr" lang="en-US" ';
      $input_required = ' required ';
      wp_enqueue_style("fontawesome","//use.fontawesome.com/releases/v5.13.1/css/all.css", array(), '5.13.1', 'all');
      wp_enqueue_style("wp-color-picker");
      wp_enqueue_script("wp-color-picker");
      wp_enqueue_style("{$this->db_slug}", "{$this->assets_url}css/backend.css");
      wp_enqueue_script("{$this->db_slug}", "{$this->assets_url}js/backend.js", array('jquery'), null, true);

      is_rtl() AND wp_add_inline_style("{$this->db_slug}", ".form-table th {}#wpfooter, #wpbody-content *:not(.dashicons ), #wpbody-content input:not([dir=ltr]), #wpbody-content textarea:not([dir=ltr]), h1.had, .caqpde>b.fa{ font-family: bodyfont, roboto, Tahoma; }");
      echo "<h1 class='had'>".__("Pepro Blogging Assistant Configurations",$this->td)."</h1><div class=\"wrap\">";
      echo '<form method="post" action="options.php">';
      settings_fields("{$this->db_slug}_general");

      if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] == "true") { echo '<div id="message" class="updated notice is-dismissible"><p>' . _x("Settings saved successfully.", "setting-general", $this->td) . "</p></div>"; }


      $post_types = get_post_types( array( 'public' => 1 , 'exclude_from_search' => 0) , 'objects');

      $headings = ""; $tabContent = "";
      $headingsAfter = ""; $tabContentAfter = "";

      $headingsAfter .= "<a id='opt-others' class='cpt-options-container cpt--others-opt'>
      <label for='tab--checkbox--others-opt'>". _x("Other Options","setting-page",$this->td)."</label>
      </a>";

      $tabContentAfter .= "<input type='radio' name='current-tab-active' id='tab--checkbox--others-opt' class='cpt-tabs cpt--others-opt' />
      <div class='cpt-options cpt--others-opt'><p><strong>". _x("You can change other options from here.","setting-page",$this->td)."</strong></p>

      <p><input type='checkbox' name=\"{$this->db_slug}-clearunistall\" id=\"{$this->db_slug}-clearunistall\"
      value='yes' " . checked($this->read_opt("{$this->db_slug}-clearunistall"), "yes", false) . " />
      <label for='{$this->db_slug}-clearunistall'>"._x("Clear Settings on Unistall", "setting-general", $this->td)."</label></p>

      <p><input type='checkbox' name=\"{$this->db_slug}-addfa\" id=\"{$this->db_slug}-addfa\"
      value='yes' " . checked($this->read_opt("{$this->db_slug}-addfa"), "yes", false) . " />
      <label for='{$this->db_slug}-addfa'>"._x("Add fontawesome v.5 to front-end", "setting-general", $this->td)."</label></p>

      <p><input type='checkbox' name=\"{$this->db_slug}-tggleadminmenubar\" id=\"{$this->db_slug}-tggleadminmenubar\"
      value='yes' " . checked($this->read_opt("{$this->db_slug}-tggleadminmenubar"), "yes", false) . " />
      <label for='{$this->db_slug}-tggleadminmenubar'>"._x("Toggle front-end wp-admin-menubar feature", "setting-general", $this->td)."</label></p>

      <p>
      <label for='{$this->db_slug}-content-wrapper'>"._x("Front-end Content Wrapper selector", "setting-general", $this->td)."</label><br>
      <input style=\"min-width: 300px;\" type='text' dir='ltr' name=\"{$this->db_slug}-content-wrapper\" id=\"{$this->db_slug}-content-wrapper\"
      value='" . $this->read_opt("{$this->db_slug}-content-wrapper", ".entry-content") . "' />
      </p>

      </div>";

      if ( $post_types ) {
        echo "<div class='pepro-blogging-assistanttant-container'>";
        foreach ( $post_types  as $post_type ) {
          $id = $slug = $post_type->name;
          $headings .= "<a class='cpt-options-container cpt--$id' id='cpt-$id'>
          <label for='tab--checkbox--$id'>". sprintf(_x("%s Options","setting-page",$this->td), $post_type->labels->singular_name)."</label>
          </a>";
          $tabContent .= "<input type='radio' name='current-tab-active' id='tab--checkbox--$id' class='cpt-tabs cpt--$id' />
          <div class='cpt-options cpt--$id'>";
          foreach ($this->get_assistants() as $section_id => $section_data) {
            $tabContent .= "<h3 id='{$slug}_{$section_id}' class='toggle-btn $section_id'>{$section_data["label"]}</h3><div class='{$slug}_{$section_id} toggle-content'>";
            foreach ($section_data["opts"] as $key => $value) {
              $name = "pepro-blogging-assistantconfig[$slug][$section_id][{$value["id"]}]";
              $name2nd = "pepro-blogging-assistantconfig[$slug][$section_id][{$value["id"]}_opt]";
              $currentValue_org = $this->read_opt("pepro-blogging-assistantconfig");
              $currentValue = "";
              $currentValue2nd = "";
              if (isset($currentValue_org[$slug][$section_id][$value["id"]])){
                $currentValue = $currentValue_org[$slug][$section_id][$value["id"]];
              }
              if (isset($currentValue_org[$slug][$section_id][$value["id"]."_opt"])){
                $currentValue2nd = $currentValue_org[$slug][$section_id][$value["id"]."_opt"];
              }

              $id = "pepro-blogging-assistantconfig_{$slug}_{$section_id}_{$value["id"]}";
              $tabContent .= "
              <div class='setting-level-1 $slug $section_id {$value["id"]}'>
              <input class='checkbox-level-1' type='checkbox' name=\"$name\" id='$id' value='yes' ".checked( $currentValue , "yes", false)." />
              <label class='setting-label-2' for='$id'>{$value["label"]}</label>
              <div class='setting-level-2'>".$this->print_opts($value["opts"], $name2nd, $currentValue2nd)."</div>
              </div>";
            }
            $tabContent .= "</div>";
          }
          $tabContent .= "</div>";
        }
        echo "<div class='tab-container'><div class='tab-heading'>{$headings}{$headingsAfter}</div><div class='tab-content'>{$tabContent}{$tabContentAfter}</div></div>";
        echo "</div>";
      }else{
        echo "<div class='notice notice-error is-dismissible'><p>" . __( "Sorry, There's a problem and we could not find any post type in your blog.",$this->td) . "</p></div>";
      }



      echo '<div class="submtCC">';
      submit_button(__("Save setting", $this->td), "primary submt", "submit", false);
      echo "</form></div></div>";
      $tcona = ob_get_contents();
      ob_end_clean();
      print $tcona;
    }
    public function admin_init($hook)
    {
      $peproBloggingAssistant_class_options = $this->get_setting_options();
      foreach ($peproBloggingAssistant_class_options as $sections) {
        foreach ($sections["data"] as $id=>$def) {
          add_option($id, $def);
          register_setting($sections["name"], $id);
        }
      }
    }
    public function admin_enqueue_scripts($hook)
    {
      wp_enqueue_style("{$this->db_slug}-backend-all", "{$this->assets_url}css/backend-all.css", array(), '1.0', 'all');
    }
    public function wp_enqueue_scripts($hook)
    {
      if ("yes" == $this->read_opt("{$this->db_slug}-addfa")){
        wp_enqueue_style("fontawesome","//use.fontawesome.com/releases/v5.13.1/css/all.css", array(), '5.13.1', 'all');
      }
      wp_enqueue_style("{$this->db_slug}", "{$this->assets_url}css/frontend.css");
      wp_register_script("{$this->db_slug}", "{$this->assets_url}js/frontend.js", array('jquery'), current_time( "timestamp" ), true);
      wp_localize_script( "{$this->db_slug}", "_pba", array(
        "id" => get_the_id(),
        "name" => get_the_title(),
        "url" => get_the_permalink(),
        "tggleadminmenubar" => ("yes" == $this->read_opt("{$this->db_slug}-tggleadminmenubar")),
        "contentWrapper" => $this->read_opt("{$this->db_slug}-content-wrapper",".entry-content"),
      ));
      wp_enqueue_script("{$this->db_slug}");
    }
    /* common functions */
    public function read_opt($mc, $def="")
    {
      return get_option($mc) <> "" ? get_option($mc) : $def;
    }
    public function plugins_row_links($links)
    {
      foreach ($this->get_manage_links() as $title => $href) {
        array_unshift($links, "<a href='$href' target='_self'>$title</a>");
      }
      $a = new SimpleXMLElement($links["deactivate"]);
      $this->deactivateURI = "<a href='".$a['href']."'>".$this->deactivateICON.$a[0]."</a>";
      unset($links["deactivate"]);
      return $links;
    }
    public function plugin_row_meta($links, $file)
    {
      if ($this->plugin_basename === $file) {
        // unset($links[1]);
        unset($links[2]);
        $icon_attr = array(
          'style' => array(
            'font-size: larger;',
            'line-height: 1rem;',
            'display: inline;',
            'vertical-align: text-top;',
          ),
        );
        foreach ($this->get_meta_links() as $id => $link) {
          $title = (!empty($link['icon'])) ? self::do_icon($link['icon'], $icon_attr) . ' ' . esc_html($link['title']) : esc_html($link['title']);
          $links[ $id ] = '<a href="' . esc_url($link['url']) . '" title="'.esc_attr($link['description']).'" target="'.(empty($link['target'])?"_blank":$link['target']).'">' . $title . '</a>';
        }
        $links[0] = $this->versionICON . $links[0];
        $links[1] = $this->authorICON . $links[1];
        $links["deactivate"] = $this->deactivateURI;
      }
      return $links;
    }
    public static function do_icon($icon, $attr = array(), $content = '')
    {
      $class = '';
      if (false === strpos($icon, '/') && 0 !== strpos($icon, 'data:') && 0 !== strpos($icon, 'http')) {
        // It's an icon class.
        $class .= ' dashicons ' . $icon;
      } else {
        // It's a Base64 encoded string or file URL.
        $class .= ' vaa-icon-image';
        $attr   = self::merge_attr(
          $attr, array(
            'style' => array( 'background-image: url("' . $icon . '") !important' ),
          )
        );
      }

      if (! empty($attr['class'])) {
        $class .= ' ' . (string) $attr['class'];
      }
      $attr['class']       = $class;
      $attr['aria-hidden'] = 'true';

      $attr = self::parse_to_html_attr($attr);
      return '<span ' . $attr . '>' . $content . '</span>';
    }
    public static function parse_to_html_attr($array)
    {
      $str = '';
      if (is_array($array) && ! empty($array)) {
        foreach ($array as $attr => $value) {
          if (is_array($value)) {
            $value = implode(' ', $value);
          }
          $array[ $attr ] = esc_attr($attr) . '="' . esc_attr($value) . '"';
        }
        $str = implode(' ', $array);
      }
      return $str;
    }
    public function _callback($a)
    {
      return $a;
    }
  }
  /**
  * load plugin and load textdomain then set a global varibale to access plugin class!
  *
  * @version 1.0.0
  * @since   1.0.0
  * @license https://pepro.dev/license Pepro.dev License
  */
  add_action(
    "plugins_loaded", function () {
      global $peproBloggingAssistant;
      load_plugin_textdomain("pepro-blogging-assistant", false, dirname(plugin_basename(__FILE__))."/languages/");
      $peproBloggingAssistant = new peproBloggingAssistant;
      register_uninstall_hook(__FILE__, array("peproBloggingAssistant", "uninstall_hook"));
    }
  );
}
/*##################################################
Lead Developer: [amirhosseinhpv](https://hpv.im/)
##################################################*/
