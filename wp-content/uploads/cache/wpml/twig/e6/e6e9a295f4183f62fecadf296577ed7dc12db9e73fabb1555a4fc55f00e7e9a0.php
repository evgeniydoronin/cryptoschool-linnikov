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

/* tooltip.twig */
class __TwigTemplate_84a7581b21740520be227da8a15bf5c1eb1f48b7a21b4e70ce90460228ae8bf8 extends \WPML\Core\Twig\Template
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
        echo "<a href=\"#\" class=\"js-wpml-ls-tooltip-open wpml-ls-tooltip-open otgs-ico-help\" data-content=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute(($context["content"] ?? null), "text", []), "html_attr");
        echo "\" data-link-text=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["content"] ?? null), "link", []), "text", []), "html_attr");
        echo "\" data-link-url=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["content"] ?? null), "link", []), "url", []), "html_attr");
        echo "\" data-link-target=\"";
        echo \WPML\Core\twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["content"] ?? null), "link", []), "target", []), "html_attr");
        echo "\"></a>";
    }

    public function getTemplateName()
    {
        return "tooltip.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  32 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<a href=\"#\" class=\"js-wpml-ls-tooltip-open wpml-ls-tooltip-open otgs-ico-help\" data-content=\"{{ content.text|e('html_attr') }}\" data-link-text=\"{{ content.link.text|e('html_attr') }}\" data-link-url=\"{{ content.link.url|e('html_attr') }}\" data-link-target=\"{{ content.link.target|e('html_attr') }}\"></a>", "tooltip.twig", "/var/www/html/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/tooltip.twig");
    }
}
