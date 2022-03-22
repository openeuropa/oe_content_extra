<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_content_extra\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\oe_content_entity_organisation\Entity\Organisation;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\sparql_entity_storage\Kernel\SparqlKernelTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the content extra fields.
 */
class ContentExtraFieldsTest extends SparqlKernelTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * Organisation for content extra bundle.
   */
  const ORGANISATION_BUNDLE = 'oe_cx_project_stakeholder';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'composite_reference',
    'datetime',
    'datetime_range',
    'entity_reference_revisions',
    'field_group',
    'file',
    'image',
    'inline_entity_form',
    'link',
    'maxlength',
    'media',
    'node',
    'oe_content',
    'oe_content_departments_field',
    'oe_content_documents_field',
    'oe_content_entity',
    'oe_content_entity_contact',
    'oe_content_entity_organisation',
    'oe_content_extra_project',
    'oe_content_featured_media_field',
    'oe_content_project',
    'oe_content_reference_code_field',
    'oe_media',
    'options',
    'rdf_skos',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('oe_organisation');
    module_load_include('install', 'oe_content_documents_field');
    oe_content_documents_field_install(FALSE);

    $this->installConfig([
      'media',
      'node',
      'oe_content',
      'oe_content_entity_organisation',
      'oe_content_departments_field',
      'oe_content_documents_field',
      'oe_content_reference_code_field',
      'oe_content_featured_media_field',
      'oe_content_project',
      'oe_content_extra_project',
    ]);

    module_load_include('install', 'oe_content_extra_project');
    oe_content_extra_project_install(FALSE);
  }

  /**
   * Test the content extra fields.
   */
  public function testFieldsStorage(): void {
    // Create file, media and organisation.
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $this->createMediaType('image', ['id' => 'image']);
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
    $organisation = Organisation::create([
      'bundle' => 'oe_cx_project_stakeholder',
      'name' => 'Team Lead',
      'oe_cx_contribution_budget' => '19.9',
    ]);
    $organisation->save();

    $values = [
      'type' => 'oe_project',
      'title' => 'My node title',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
      'oe_cx_achievements_and_milestone' => 'Test achievements and milestone text',
      'oe_cx_gallery' => [
        'target_id' => $media->id(),
      ],
      'oe_cx_impacts' => 'Test impacts text',
      'oe_cx_objective' => 'Test objective text',
      'oe_cx_lead_contributors' => $organisation,
    ];

    // Create a page node and get the organisation.
    $entity_type_manager = $this->container->get('entity_type.manager')->getStorage('node');
    $node = $entity_type_manager->create($values);
    $node_organisation = Organisation::load($node->get('oe_cx_lead_contributors')->target_id);

    // Assert the content extra field values.
    $this->assertEquals('My node title', $node->label());
    $this->assertEquals($values['oe_cx_gallery']['target_id'], $node->get('oe_cx_gallery')->target_id);
    $this->assertEquals($values['oe_cx_impacts'], $node->get('oe_cx_impacts')->value);
    $this->assertEquals($values['oe_cx_objective'], $node->get('oe_cx_objective')->value);
    $this->assertEquals($values['oe_cx_achievements_and_milestone'], $node->get('oe_cx_achievements_and_milestone')->value);
    $this->assertEquals('Team Lead', $node_organisation->getName());

    // Assert the content extra field config.
    $this->assertEquals('entity_reference', $node->get('oe_cx_gallery')->getFieldDefinition()->getType());
    $this->assertEquals('text_long', $node->get('oe_cx_impacts')->getFieldDefinition()->getType());
    $this->assertEquals('text_long', $node->get('oe_cx_objective')->getFieldDefinition()->getType());
    $this->assertEquals('text_long', $node->get('oe_cx_achievements_and_milestone')->getFieldDefinition()->getType());
    $this->assertEquals('entity_reference_revisions', $node->get('oe_cx_lead_contributors')->getFieldDefinition()->getType());

    // Assert bundles.
    $lead_contributors_bundle = $node->get('oe_cx_lead_contributors')->getFieldDefinition()->getsetting('handler_settings')['target_bundles'];
    $project_participants = $node->get('oe_project_participants')->getFieldDefinition()->getsetting('handler_settings')['target_bundles'];
    $this->assertEquals(self::ORGANISATION_BUNDLE, key($project_participants));
    $this->assertEquals(self::ORGANISATION_BUNDLE, key($lead_contributors_bundle));

    // Contribution field.
    $this->assertEquals($organisation->get('oe_cx_contribution_budget')->value, $node_organisation->get('oe_cx_contribution_budget')->value);
    $this->assertEquals('float', $node_organisation->get('oe_cx_contribution_budget')->getFieldDefinition()->getType());
  }

}
