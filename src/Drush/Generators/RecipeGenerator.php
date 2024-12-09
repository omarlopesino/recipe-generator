<?php

declare(strict_types=1);

namespace kevinquillen\Drush\Generators;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Validator\Required;
use Symfony\Component\Console\Question\Question;
use DrupalCodeGenerator\GeneratorType;

/**
 * Implements Recipe generator command.
 */
#[Generator(
  name: 'recipe',
  description: 'Generates a recipe',
  hidden: true,
  type: GeneratorType::OTHER,
  templatePath: __DIR__ . '/../../../templates'
)]
final class RecipeGenerator extends BaseGenerator {

  /**
   * This is a temporary workaround until Drupal generator supports Recipes.
   */
  public const EXTENSION_TYPE_RECIPE = 0x04;

  /**
   * {@inheritdoc}
   */
  protected ?int $extensionType = self::EXTENSION_TYPE_RECIPE;

  /**
   * The Drush generator command name.
   *
   * @var string
   */
  protected string $name = 'recipe';

  /**
   * The Drush generator command description.
   *
   * @var string
   */
  protected string $description = 'Generates a Recipe for adding new functionality to Drupal.';

  /**
   * {@inheritdoc}
   */
  protected function getExtensionList(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, AssetCollection $assets): void {
    $interviewer = $this->createInterviewer($vars);
    $vars['recipe_name'] = $interviewer->ask('What is the name of this recipe?', 'My Custom Recipe', new Required());
    $vars['recipe_directory'] = $interviewer->ask('In what directory should this recipe be saved under /recipes (ex. "my-recipe")?', 'my-custom-recipe', new Required());
    $vars['recipe_type'] = $interviewer->ask('What type of recipe is this (Site, Content Type, Workflow, etc)?', NULL, new Required());
    $vars['recipe_description'] = $interviewer->ask('What does this recipe do?', NULL, new Required());
    $vars['composer'] = $this->collectComposerInfo($vars);
    $vars['modules'] = $this->collectModules($vars);
    $vars['config'] = $this->collectConfig($vars);

    if (!empty($vars['composer'])) {
      $assets->addFile('composer.json', 'composer/composer.json.twig');
    }

    $assets->addFile('recipe.yml', 'recipe/recipe.yml.twig');
  }

  /**
   * Returns destination for generated recipes.
   *
   * This is a temporary workaround until Drupal generator supports Recipes.
   */
  protected function getDestination(array $vars): string {
    return \DRUPAL_ROOT . '/recipes/custom/' . $vars['recipe_directory'];
  }

  /**
   * Collects Composer related information from the user.
   *
   * @param array $vars
   *   The input vars.
   * @param bool $default
   *   The users choice.
   *
   * @return array
   */
  protected function collectComposerInfo(array &$vars, bool $default = TRUE): array {
    $interviewer = $this->createInterviewer($vars);
    $vars['composer'] = [];

    if (!$interviewer->confirm('Would you like to add a composer.json file for this recipe? This will let you declare dependencies.', $default)) {
      return $vars['composer'];
    }

    $question = new Question('Enter the vendor name.', 'drupal');
    $vendor_name = $this->io()->askQuestion($question);

    $question = new Question('Enter the package name.', 'my-custom-recipe');
    $package_name = $this->io()->askQuestion($question);

    $question = new Question('What is your name? This will be set as the author.', 'Developer');
    $author_name = $this->io()->askQuestion($question);

    $vars['composer']['vendor_name'] = $vendor_name;
    $vars['composer']['package_name'] = $package_name;
    $vars['composer']['author_name'] = $author_name;

    while (TRUE) {
      $question = new Question('Enter the name of the dependency to add (ex. drupal/pathauto).');
      $dependency = $this->io()->askQuestion($question);

      if (!$dependency) {
        break;
      }

      $question = new Question('Enter the version of this dependency to require (ex. ^1.0)');
      $version = $this->io()->askQuestion($question);

      $vars['composer']['dependencies'][] = [
        'name' => $dependency,
        'version' => $version,
      ];
    }

    return $vars['composer'];
  }

  /**
   * Collects module related information from the user.
   *
   * @param array $vars
   *   The input vars.
   * @param bool $default
   *   The users choice.
   *
   * @return array
   */
  protected function collectModules(array &$vars, bool $default = TRUE): array {
    $interviewer = $this->createInterviewer($vars);
    $vars['modules'] = [];

    if (!$interviewer->confirm('Would you like to add modules to install for this recipe?', $default)) {
      return $vars['modules'];
    }

    while (TRUE) {
      $question = new Question('Enter the name of the module to add (ex. node).');
      $module = $this->io()->askQuestion($question);

      if (!$module) {
        break;
      }

      $vars['modules'][] = $module;
    }

    return $vars['modules'];
  }

  /**
   * Collects configuration related information from the user.
   *
   * @param array $vars
   *   The input vars.
   * @param bool $default
   *   The users choice.
   *
   * @return array
   */
  protected function collectConfig(array &$vars, bool $default = TRUE): array {
    $interviewer = $this->createInterviewer($vars);
    $vars['config'] = [];

    if ($interviewer->confirm('Would you like to run specific config imports for this recipe?', $default)) {
      while (TRUE) {
        $config = [];
        $question = new Question('What module do you want to import config for (ex. node)?');
        $module = $this->io()->askQuestion($question);

        if (!$module) {
          break;
        }

        if (!$interviewer->confirm("Do you want to import all config for $module (including optional config)?", $default)) {
          while (TRUE) {
            $question = new Question("Enter the config file you want to import for $module (without the .yml extension).");
            $filename = $this->io()->askQuestion($question);

            if (!$filename) {
              break;
            }

            $config[] = $filename;
          }
        }

        $vars['config']['import'][] = [
          'module_name' => $module,
          'config' => (empty($config)) ? '*' : $config,
        ];
      }
    }

    if ($interviewer->confirm('Would you like to run config actions for this recipe?', $default)) {
      // ask for config actions
      // ask for action type
    }

    return $vars['config'];
  }

}
