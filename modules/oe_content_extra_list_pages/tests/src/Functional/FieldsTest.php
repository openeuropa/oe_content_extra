<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_content_extra_list_pages\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the List pages content extra fields.
 */
class FieldsTest extends BrowserTestBase {

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
    'oe_content_extra_list_pages_test',
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
    $this->drupalGet('node/add/oe_list_page');
    $page->fillField('Title', 'List pages page test');
    $page->fillField('Introduction', 'Summary text');
    $page->fillField('Content owner', 'Associated African States and Madagascar (http://publications.europa.eu/resource/authority/corporate-body/AASM)');
    $page->fillField('oe_featured_media[0][featured_media][target_id]', 'Image test (1)');
    $page->pressButton('Save');

    // Assert the fields values.
    $assert_session->pageTextContains('List pages page test');
    $assert_session->pageTextContains('Summary text');
    $assert_session->linkExists($media->get('name')->value);
    $link = $this->getSession()->getPage()->findLink($media->get('name')->value);
    $this->assertEquals($media->toUrl()->toString(), $link->getAttribute('href'));
  }

}
