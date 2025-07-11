<?php

namespace WPML\Core;

use \WPML\Core\Twig\Environment;
use \WPML\Core\Twig\Error\LoaderError;
use \WPML\Core\Twig\Error\RuntimeError;
use \WPML\Core\Twig\Markup;
use \WPML\Core\Twig\Sandbox\SecurityError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedTagError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedFilterError;
use \WPML\Core\Twig\Sandbox\SecurityNotAllowedFunctionError;
use \WPML\Core\Twig\Source;
use \WPML\Core\Twig\Template;

/* button-add-new-ls.twig */
class __TwigTemplate_d2611a18f88302bc6c57331798b72ebc259073c966225e069f5f852e885459d5 extends \WPML\Core\Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<p class=\"alignright\">

\t";
        // line 3
        $context["add_tooltip"] = ($context["tooltip_all_assigned"] ?? null);
        // line 4
        echo "
\t";
        // line 5
        if ((($context["existing_items"] ?? null) == 0)) {
            // line 6
            echo "\t\t";
            $context["add_tooltip"] = ($context["tooltip_no_item"] ?? null);
            // line 7
            echo "\t";
        }
        // line 8
        echo "
\t";
        // line 9
        if ((($context["settings_items"] ?? null) >= ($context["existing_items"] ?? null))) {
            // line 10
            echo "\t\t";
            $context["disabled"] = true;
            // line 11
            echo "\t";
        }
        // line 12
        echo "
\t<span class=\"js-wpml-ls-tooltip-wrapper";
        // line 13
        if ( !($context["disabled"] ?? null)) {
            echo " hidden";
        }
        echo "\">
        ";
        // line 14
        $this->loadTemplate("tooltip.twig", "button-add-new-ls.twig", 14)->display(twig_array_merge($context, ["content" => ($context["add_tooltip"] ?? null)]));
        // line 15
        echo "    </span>

\t<button class=\"button-secondary js-wpml-ls-open-dialog wpml-button base-btn wpml-button--outlined\"";
        // line 17
        if (($context["disabled"] ?? null)) {
            echo " disabled=\"disabled\"";
        }
        // line 18
        echo "\t\t\tdata-target=\"";
        echo \WPML\Core\twig_escape_filter($this->env, ($context["button_target"] ?? null), "html", null, true);
        echo "\">+ ";
        echo \WPML\Core\twig_escape_filter($this->env, ($context["button_label"] ?? null), "html", null, true);
        echo "</button>
</p>
";
    }

    public function getTemplateName()
    {
        return "button-add-new-ls.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  79 => 18,  75 => 17,  71 => 15,  69 => 14,  63 => 13,  60 => 12,  57 => 11,  54 => 10,  52 => 9,  49 => 8,  46 => 7,  43 => 6,  41 => 5,  38 => 4,  36 => 3,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<p class=\"alignright\">

\t{% set add_tooltip = tooltip_all_assigned %}

\t{% if  existing_items == 0 %}
\t\t{% set add_tooltip = tooltip_no_item %}
\t{% endif %}

\t{% if settings_items >=  existing_items %}
\t\t{% set disabled = true %}
\t{% endif %}

\t<span class=\"js-wpml-ls-tooltip-wrapper{% if not disabled %} hidden{% endif %}\">
        {% include 'tooltip.twig' with { \"content\": add_tooltip } %}
    </span>

\t<button class=\"button-secondary js-wpml-ls-open-dialog wpml-button base-btn wpml-button--outlined\"{% if disabled %} disabled=\"disabled\"{% endif %}
\t\t\tdata-target=\"{{ button_target }}\">+ {{ button_label }}</button>
</p>
", "button-add-new-ls.twig", "/var/www/html/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/button-add-new-ls.twig");
    }
}
