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

/* table-slots.twig */
class __TwigTemplate_8a457ee91358b05b82d6a88b04c5cbc8959bd073f18359cdd4113b8b6c7d1d42 extends \WPML\Core\Twig\Template
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
        if ((($context["slot_type"] ?? null) == "statics")) {
            // line 2
            echo "\t";
            $context["is_static"] = true;
            // line 3
            echo "\t";
            $context["table_id"] = ((("wpml-ls-slot-list-" . ($context["slot_type"] ?? null)) . "-") . ($context["slug"] ?? null));
        } else {
            // line 5
            echo "\t";
            $context["table_id"] = ("wpml-ls-slot-list-" . ($context["slot_type"] ?? null));
        }
        // line 7
        echo "
";
        // line 8
        if (twig_in_filter(($context["slug"] ?? null), [0 => "footer", 1 => "post_translations"])) {
            // line 9
            echo "    ";
            $context["label_action"] = $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", []), "label_action", []);
        } else {
            // line 11
            echo "    ";
            $context["label_action"] = $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", []), "label_actions", []);
        }
        // line 13
        echo "
<table id=\"";
        // line 14
        echo \WPML\Core\twig_escape_filter($this->env, ($context["table_id"] ?? null), "html", null, true);
        echo "\" class=\"js-wpml-ls-slot-list wpml-ls-slot-list\"";
        if ( !($context["slots_settings"] ?? null)) {
            echo " style=\"display:none;\"";
        }
        echo ">
    <thead>
    <tr>
        <th>";
        // line 17
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", []), "label_preview", []), "html", null, true);
        echo "</th>
        ";
        // line 18
        if ( !($context["is_static"] ?? null)) {
            echo "<th>";
            echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", []), "label_position", []), "html", null, true);
            echo "</th>";
        }
        // line 19
        echo "        <th";
        if ( !($context["is_static"] ?? null)) {
            echo " colspan=\"2\"";
        }
        echo ">";
        echo \WPML\Core\twig_escape_filter($this->env, ($context["label_action"] ?? null), "html", null, true);
        echo "</th></tr>
    </thead>
    <tbody>
    ";
        // line 22
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["slots_settings"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["slug"] => $context["slot_settings"]) {
            // line 23
            echo "        ";
            $this->loadTemplate("table-slot-row.twig", "table-slots.twig", 23)->display(twig_array_merge($context, ["slug" =>             // line 25
$context["slug"], "slot_type" =>             // line 26
($context["slot_type"] ?? null), "slot_settings" =>             // line 27
$context["slot_settings"], "slots" =>             // line 28
($context["slots"] ?? null)]));
            // line 31
            echo "    ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['slug'], $context['slot_settings'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 32
        echo "    </tbody>
</table>";
    }

    public function getTemplateName()
    {
        return "table-slots.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  130 => 32,  116 => 31,  114 => 28,  113 => 27,  112 => 26,  111 => 25,  109 => 23,  92 => 22,  81 => 19,  75 => 18,  71 => 17,  61 => 14,  58 => 13,  54 => 11,  50 => 9,  48 => 8,  45 => 7,  41 => 5,  37 => 3,  34 => 2,  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% if slot_type == 'statics' %}
\t{% set is_static = true %}
\t{% set table_id = 'wpml-ls-slot-list-' ~ slot_type ~ '-' ~ slug %}
{% else %}
\t{% set table_id = 'wpml-ls-slot-list-' ~ slot_type %}
{% endif %}

{% if slug in ['footer', 'post_translations'] %}
    {% set label_action = strings.misc.label_action %}
{% else %}
    {% set label_action = strings.misc.label_actions %}
{% endif %}

<table id=\"{{ table_id }}\" class=\"js-wpml-ls-slot-list wpml-ls-slot-list\"{% if not slots_settings %} style=\"display:none;\"{% endif %}>
    <thead>
    <tr>
        <th>{{ strings.misc.label_preview }}</th>
        {% if not is_static %}<th>{{ strings.misc.label_position }}</th>{% endif %}
        <th{% if not is_static %} colspan=\"2\"{% endif %}>{{ label_action }}</th></tr>
    </thead>
    <tbody>
    {% for slug, slot_settings in slots_settings %}
        {% include 'table-slot-row.twig'
            with {
                \"slug\": slug,
                \"slot_type\": slot_type,
                \"slot_settings\": slot_settings,
                \"slots\": slots,
            }
        %}
    {% endfor %}
    </tbody>
</table>", "table-slots.twig", "/var/www/html/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/table-slots.twig");
    }
}
