<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

/**
 * Translates the button settings into CSS classes and CSS custom properties for the storefront
 *
 * Custom values are applied through CSS custom properties that are set from JavaScript, which keeps the checkout
 * compatible with a strict Content Security Policy.
 */
class ButtonStyle
{
    public const CSS_VAR_BACKGROUND = '--idenfy-button-background';
    public const CSS_VAR_COLOR = '--idenfy-button-color';
    public const CSS_VAR_RADIUS = '--idenfy-button-radius';

    /** @var Config */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * CSS classes of the primary verification button
     *
     * @return string
     */
    public function getCssClass(): string
    {
        $classes = ['action', 'primary', 'idenfy-button'];

        if ($this->config->isCustomButtonStyle()) {
            if ($this->config->getButtonBackgroundColor() !== null) {
                $classes[] = 'idenfy-button--background';
            }
            if ($this->config->getButtonTextColor() !== null) {
                $classes[] = 'idenfy-button--color';
            }
            if ($this->config->getButtonBorderRadius() !== null) {
                $classes[] = 'idenfy-button--radius';
            }
        }

        return implode(' ', $classes);
    }

    /**
     * CSS custom properties holding the custom button style
     *
     * @return array<string, string>
     */
    public function getCssVariables(): array
    {
        if (!$this->config->isCustomButtonStyle()) {
            return [];
        }

        $variables = [
            self::CSS_VAR_BACKGROUND => $this->config->getButtonBackgroundColor(),
            self::CSS_VAR_COLOR => $this->config->getButtonTextColor(),
        ];

        $radius = $this->config->getButtonBorderRadius();
        $variables[self::CSS_VAR_RADIUS] = $radius !== null ? $radius . 'px' : null;

        return array_filter($variables, static function ($value) {
            return $value !== null;
        });
    }
}
