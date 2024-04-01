<?php

namespace Drupal\advanced_language_selector\Plugin\Block;

use Drupal\advanced_language_selector\Services\StyleManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an advanced language selector block.
 *
 * @Block(
 *   id = "advanced_language_selector_block",
 *   admin_label = @Translation("Advanced language selector block"),
 *   category = @Translation("Language block")
 * )
 */
class LanguageSelectorBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Style Manager.
   *
   * @var \Drupal\advanced_language_selector\Services\StyleManagerInterface
   */
  private $styleManager;

  /**
   * The Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * The Route Match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The internal areas array.
   *
   * @var array
   */
  private array $areas = [];

  /**
   * Constructs a new LanguageSelectorBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\advanced_language_selector\Services\StyleManagerInterface $style_manager
   *   The Style Manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, RequestStack $request_stack, ModuleHandlerInterface $module_handler, StyleManagerInterface $style_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $language_manager);
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
    $this->styleManager = $style_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('advanced_language_selector.style_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get all available styles defined in the config.
    $styles = $this->styleManager->getAvailableStyles();

    // Get the style selector and append to an area.
    $styleSelector = $this->styleManager->getStyleSelector($styles);
    $this->addArea($styleSelector);

    // Append each style to an area.
    foreach ($styles as $style) {
      $this->addArea($style);
    }

    // Build form areas.
    foreach ($this->areas as $areaKey => $area) {
      $this->buildItem($form, $this->configuration, $areaKey, $area);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $userValues = $form_state->getValues();
    foreach ($userValues as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    /*
    See State required asterisk doesn't show:
    https://www.drupal.org/files/issues/2023-08-18/2912092-67-10-1-x.patch.
    Attribute "required" doesn't work well with drupal states, so this
    is a workaround to only check for validation errors on selected
    theme. The other theme validation errors will be ignored.
     */
    $selectedTheme = $form_state->getValue('look_and_feel')['theme'];
    $errors = $form_state->getErrors();
    foreach ($errors as $key => $errorData) {
      // If validation error is in another theme, ignore it (remove it).
      if (!str_starts_with($key, "settings][$selectedTheme]")) {
        unset($errors[$key]);
      }
    }

    // Clean all error forms.
    $form_state->clearErrors();

    // Process selected theme errors (if there are...)
    if (count($errors) > 0) {
      $complete_form = $form_state->getCompleteForm();
      foreach ($errors as $key => $errorData) {
        $tks = explode('][', $key);
        // Access to the form element with the error.
        $result = $complete_form;
        foreach ($tks as $key) {
          if (isset($result[$key])) {
            $result = $result[$key];
          }
          else {
            $result = NULL;
            break;
          }
        }
        if ($result) {
          $form_state->setError($result);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $type = $this->getDerivativeId();
    $url = $this->routeMatch->getRouteObject() ? Url::fromRouteMatch($this->routeMatch) : Url::fromRoute('<front>');
    $links = $this->languageManager->getLanguageSwitchLinks($type, $url);

    // If there are no links is because Translation is not installed,
    // so create default link with default language.
    if (!$links) {
      $defLang = $this->languageManager->getDefaultLanguage();
      $links = (object) [
        'links' => [$defLang->getId() => []],
      ];
    }

    $this->parseLinks($links);

    $type = $this->configuration['look_and_feel']['theme'] ?? 'bootstrap_dropdown';
    $style = $this->styleManager->getStyle($type);
    $block_configuration = $this->configuration[$type];

    // If block is not built by the Block Manager (for example, rendered directly in twig)
    // we must provide it a default configuration.
    if (is_null($block_configuration)) {
      $defaultConfig = json_decode('{"general":{"id":"langSelector","css":"","text_transformation":"default","load_external_bootstrap":0,"external_bootstrap_library":"advanced_language_selector\/bootstrap"},"display":{"selected_item":{"css":"btn-primary","show":{"icons":"icons","lang_code":0,"lang_name":0},"icon_height":"25","icon_alignment":"right"},"items":{"show":{"icons":"icons","lang_name":"lang_name","lang_code":0},"icon_height":"25","icon_alignment":"left"}}}');
      $block_configuration = $defaultConfig;
    }

    if (isset($links->links)) {
      $build = [
        '#theme' => $style['theme'],
        '#links' => $links->links,
        '#link_active' => $links->links[$this->languageManager->getCurrentLanguage()->getId()],
        '#templates_location' => $style['templates_location'],
        '#attached' => ['library' => $style['libraries']],
        '#configuration' => $block_configuration,
        '#attributes' => [
          'class' => [
            "language-switcher-{$links->method_id}",
          ],
        ],
        '#set_active_class' => TRUE,
      ];
    }
    return $build;
  }

  /**
   * Adds an area to the internal areas array.
   *
   * It's mandatory that new area contains a key 'properties', otherwise
   * the area will be ignored.
   *
   * @param array $area
   *   New area.
   *
   * @return void
   */
  private function addArea(array $area = []): void {
    if (isset($area['properties'])) {
      foreach ($area['properties'] as $areaKey => $areaDef) {
        $this->areas[$areaKey] = $areaDef;
      }
    }
  }

  /**
   * Recursive function to build hierarchical form fields.
   *
   * @param array $form
   *   The form array.
   * @param array $configuration
   *   The form configuration array.
   * @param string $itemKey
   *   The form item key.
   * @param array $item
   *   The field definition.
   *
   * @return void
   */
  private function buildItem(array &$form, array &$configuration, string $itemKey, array &$item) {
    if ($item['type']) {
      $default_value = $item['default_value'] ?? NULL;
      $value = $configuration[$itemKey] ?? $default_value;

      if (!isset($configuration[$itemKey])) {
        $configuration[$itemKey] = [];
        $form[$itemKey] = [];
      }

      $form[$itemKey] = $this->buildFormField($item, $value);
    }

    if (isset($item['properties'])) {
      foreach ($item['properties'] as $key => $property) {
        $this->buildItem($form[$itemKey], $configuration[$itemKey], $key, $property);
      }
    }
    else {
      $configuration[$itemKey] = $default_value;
    }
  }

  /**
   * Builds a form field based on definition.
   *
   * @param array $definition
   *   Field definition.
   * @param mixed $value
   *   Field default value.
   *
   * @return array
   *   Field array.
   */
  private function buildFormField(array $definition, $value): array {
    $field = [];
    foreach ($definition as $propertyKey => $propertyValue) {
      if ($propertyKey != 'properties') {
        $field['#' . $propertyKey] = $propertyValue;
      }
    }
    $field['#default_value'] = $value;
    return $field;
  }

  /**
   * Set the custom info to the links.
   *
   * @param \stdClass $links
   *   The links.
   *
   * @return void
   */
  private function parseLinks(\stdClass &$links): void {
    $current_language = $this->languageManager->getCurrentLanguage();
    foreach (array_keys($links->links) as $langcode) {
      $language = $this->languageManager->getLanguage($langcode);
      $flagcode = $langcode == 'en' ? 'gb' : $langcode;
      $links->links[$langcode]['langcode'] = $language->getId();
      $links->links[$langcode]['icon'] = $this->getFlagIcon($flagcode);
      $links->links[$langcode]['uri'] = $this->getCurrentUri($langcode);
      $links->links[$langcode]['current_langcode'] = $current_language->getId();
    }
  }

  /**
   * Return the flag icon path.
   *
   * @param string $langCode
   *   The lang code.
   *
   * @return string
   *   The flag icon path that represent the lang code.
   */
  private function getFlagIcon(string $langCode): string {
    $module = $this->moduleHandler->getModule("advanced_language_selector");
    $flagIcon = $module->getPath() . "/assets/flags/$langCode.svg";
    if (file_exists($flagIcon)) {
      return '/' . $flagIcon;
    }
    else {
      return '/' . $module->getPath() . "/assets/flags/no-flag.svg";
    }
  }

  /**
   * Get current uri in the language specified.
   *
   * @param string $destLanguage
   *   Destination language.
   *
   * @return string
   *   Current URI in the specified language code.
   */
  private function getCurrentUri(string $destLanguage = 'en'): string {
    $defaultLanguageCode = $this->languageManager->getDefaultLanguage()->getId();
    $currentLanguageCode = $this->languageManager->getCurrentLanguage()->getId();
    // If there is no route match, for example when creating blocks on 404 pages
    // for logged-in users with big_pipe enabled using the front page instead.
    $current_url = $this->routeMatch->getRouteObject() ? Url::fromRouteMatch($this->routeMatch) : Url::fromRoute('<front>');
    // If link is a default language link.
    $current_uri = $this->removeLanguage($current_url->toString(), $defaultLanguageCode);
    $current_uri = $this->removeLanguage($current_uri, $currentLanguageCode);
    if ($destLanguage == $defaultLanguageCode) {
      return $current_uri;
    }
    else {
      return '/' . $destLanguage . $this->removeLanguage($current_uri, $destLanguage);
    }
  }

  /**
   * Remove the language from the specified uri.
   *
   * @param string $currentUri
   *   The uri.
   * @param string $lang
   *   The language to remove.
   *
   * @return string
   *   The uri without the language.
   */
  private function removeLanguage(string $currentUri, string $lang): string {
    $value = $currentUri;
    $pos = strpos($currentUri, '/' . $lang . '/');
    if ($pos !== FALSE) {
      $value = substr_replace($currentUri, '/', $pos, strlen('/' . $lang . '/'));
    }
    return $value;
  }

}
