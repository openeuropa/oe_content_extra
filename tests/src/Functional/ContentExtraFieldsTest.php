<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_content_extra\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the content extra fields.
 */
class ContentExtraFieldsTest extends BrowserTestBase {

  use MediaTypeCreationTrait;
  use SparqlConnectionTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_content_extra_project',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();

    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Test the content extra fields.
   */
  public function testFieldsStorage(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create file and media.
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Image test',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'Image test alt',
          'title' => 'Image test title',
        ],
      ],
    ]);
    $media->save();

    // Create a node, fill the required values.
    $this->drupalGet('node/add/oe_project');
    $page->fillField('Page title', 'Project page test');
    $page->fillField('Subject', 'financing (http://data.europa.eu/uxp/1000)');
    $page->fillField('Country', 'BE');
    $page->fillField('Body text', 'Test body text');
    $page->fillField('Teaser', 'Teaser text');
    $page->fillField('Content owner', 'Associated African States and Madagascar (http://publications.europa.eu/resource/authority/corporate-body/AASM)');

    // Add a new lead contributor.
    $page->pressButton('Add new Leader');
    $page->fillField('Objective', 'Text Objective');
    $page->fillField('Impacts', 'Text Impacts');
    $page->fillField('Achievements and milestones', 'Text Achievements and milestones');
    $page->fillField('oe_cx_gallery[0][target_id]', 'Image test (1)');
    $page->pressButton('Save');

    // Assert error and fixing on lead contributors field.
    $assert_session->pageTextContains('Name field is required.');
    $page->fillField('Name', 'Team lead');
    $page->pressButton('Save');

    // Assert the extra fields values.
    $assert_session->pageTextContains('Objective');
    $assert_session->pageTextContains('Text Objective');
    $assert_session->pageTextContains('Impacts');
    $assert_session->pageTextContains('Text Impacts');
    $assert_session->pageTextContains('Achievements and milestones');
    $assert_session->pageTextContains('Text Achievements and milestones');
    $assert_session->pageTextContains('Gallery');
    $image_element = $this->assertSession()->elementExists('css', 'img');
    $this->assertStringContainsString('image-test.png', $image_element->getAttribute('src'));
    $assert_session->pageTextContains('Lead contributors');
    $assert_session->pageTextContains('Team lead');

    // Fill the new field for oe_cx_project_stakeholder budget.
    $this->drupalGet('node/1/edit');
    $page->pressButton('Add new participant');
    $assert_session->fieldExists('Contribution to the budget');
    $page->fillField('Name', 'Developer participant');
    $page->fillField('oe_project_participants[form][0][oe_cx_contribution_budget][0][value]', '19.9');
    $page->pressButton('Edit');
    $page->fillField('oe_cx_lead_contributors[form][inline_entity_form][entities][0][form][oe_cx_contribution_budget][0][value]', '22.3');
    $page->pressButton('Save');

    // Assert new field on oe_cx_project_stakeholder budget.
    $assert_session->pageTextContains('Developer participant');
    $assert_session->pageTextContains('Contribution to the budget');
    $assert_session->pageTextContains('19.9');
    $assert_session->pageTextContains('22.3');
  }

}
