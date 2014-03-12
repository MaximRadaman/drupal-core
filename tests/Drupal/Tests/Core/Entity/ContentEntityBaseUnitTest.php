<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\ContentEntityBaseUnitTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityBase
 *
 * @group Drupal
 */
class ContentEntityBaseUnitTest extends UnitTestCase {

  /**
   * The bundle of the entity under test.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity info used for testing..
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityInfo;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The type of the entity under test.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The typed data manager used for testing.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedDataManager;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Entity\ContentEntityBase unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $values = array();
    $this->entityType = $this->randomName();
    $this->bundle = $this->randomName();

    $this->entityInfo = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityType)
      ->will($this->returnValue($this->entityInfo));
    $this->entityManager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with($this->entityType, $this->bundle)
      ->will($this->returnValue(array(
        'id' => array(
          'type' => 'integer_field',
        ),
      )));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityBase')
      ->setConstructorArgs(array($values, $this->entityType, $this->bundle))
      ->setMethods(array('languageList', 'languageLoad'))
      ->getMockForAbstractClass();
    $this->entity->expects($this->any())
      ->method('languageList')
      ->will($this->returnValue(array()));
    $this->entity->expects($this->any())
      ->method('languageLoad')
      ->will($this->returnValue(NULL));
  }

  /**
   * @covers ::isNewRevision
   * @covers ::setNewRevision
   */
  public function testIsNewRevision() {
    $this->entityInfo->expects($this->at(0))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(FALSE));
    $this->entityInfo->expects($this->at(1))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(TRUE));

    $this->assertFalse($this->entity->isNewRevision());
    $this->assertTrue($this->entity->isNewRevision());
    $this->entity->setNewRevision(TRUE);
    $this->assertTRUE($this->entity->isNewRevision());
  }

  /**
   * @covers ::isDefaultRevision
   */
  public function testIsDefaultRevision() {
    // The default value is TRUE.
    $this->assertTrue($this->entity->isDefaultRevision());
    // We override the value, but it does not affect this call.
    $this->assertTrue($this->entity->isDefaultRevision(FALSE));
    // The last call changed the return value for this call.
    $this->assertFalse($this->entity->isDefaultRevision());
  }

  /**
   * @covers ::getRevisionId
   */
  public function testGetRevisionId() {
    $this->assertNull($this->entity->getRevisionId());
  }

  /**
   * @covers ::isTranslatable
   */
  public function testIsTranslatable() {
    $this->entityManager->expects($this->at(0))
      ->method('getBundleInfo')
      ->with($this->entityType)
      ->will($this->returnValue(array(
        $this->bundle => array(
          'translatable' => TRUE,
        ),
      )));
    $this->assertTrue($this->entity->isTranslatable());
    $this->assertFalse($this->entity->isTranslatable());
  }

  /**
   * @covers ::preSaveRevision
   */
  public function testPreSaveRevision() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageControllerInterface');
    $record = new \stdClass();
    $this->entity->preSaveRevision($storage, $record);
  }

  /**
   * @covers ::getString
   */
  public function testGetString() {
    $label = $this->randomName();
    /** @var \Drupal\Core\Entity\ContentEntityBase|\PHPUnit_Framework_MockObject_MockObject $entity */
    $entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityBase')
      ->setMethods(array('label'))
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $entity->expects($this->once())
      ->method('label')
      ->will($this->returnValue($label));

    $this->assertSame($label, $entity->getString());
  }

  /**
   * @covers ::validate
   */
  public function testValidate() {
    $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');
    /** @var \Symfony\Component\Validator\ConstraintViolationList|\PHPUnit_Framework_MockObject_MockObject $empty_violation_list */
    $empty_violation_list = $this->getMockBuilder('\Symfony\Component\Validator\ConstraintViolationList')
      ->setMethods(NULL)
      ->getMock();
    $non_empty_violation_list = clone $empty_violation_list;
    $violation = $this->getMock('\Symfony\Component\Validator\ConstraintViolationInterface');
    $non_empty_violation_list->add($violation);
    $validator->expects($this->at(0))
      ->method('validate')
      ->with($this->entity)
      ->will($this->returnValue($empty_violation_list));
    $validator->expects($this->at(1))
      ->method('validate')
      ->with($this->entity)
      ->will($this->returnValue($non_empty_violation_list));
    $this->typedDataManager->expects($this->exactly(2))
      ->method('getValidator')
      ->will($this->returnValue($validator));
    $this->assertSame(0, count($this->entity->validate()));
    $this->assertSame(1, count($this->entity->validate()));
  }

  /**
   * @covers ::getConstraints
   */
  public function testGetConstraints() {
    $this->assertInternalType('array', $this->entity->getConstraints());
  }

  /**
   * @covers ::getName
   */
  public function testGetName() {
    $this->assertNull($this->entity->getName());
  }

  /**
   * @covers ::getRoot
   */
  public function testGetRoot() {
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->getRoot()));
  }

  /**
   * @covers ::getPropertyPath
   */
  public function testGetPropertyPath() {
    $this->assertSame('', $this->entity->getPropertyPath());
  }

  /**
   * @covers ::getParent
   */
  public function testGetParent() {
    $this->assertNull($this->entity->getParent());
  }

  /**
   * @covers ::setContext
   */
  public function testSetContext() {
    $name = $this->randomName();
    $parent = $this->getMock('\Drupal\Core\TypedData\TypedDataInterface');
    $this->entity->setContext($name, $parent);
  }

  /**
   * @covers ::bundle
   */
  public function testBundle() {
    $this->assertSame($this->bundle, $this->entity->bundle());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $access = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $operation = $this->randomName();
    $access->expects($this->at(0))
      ->method('access')
      ->with($this->entity, $operation)
      ->will($this->returnValue(TRUE));
    $access->expects($this->at(1))
      ->method('createAccess')
      ->will($this->returnValue(TRUE));
    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessController')
      ->will($this->returnValue($access));
    $this->assertTrue($this->entity->access($operation));
    $this->assertTrue($this->entity->access('create'));
  }

  /**
   * @covers ::label
   */
  public function testLabel() {
    // Make a mock with one method that we use as the entity's uri_callback. We
    // check that it is called, and that the entity's label is the callback's
    // return value.
    $callback_label = $this->randomName();
    $callback_container = $this->getMock(get_class());
    $callback_container->expects($this->once())
      ->method(__FUNCTION__)
      ->will($this->returnValue($callback_label));
    $this->entityInfo->expects($this->once())
      ->method('getLabelCallback')
      ->will($this->returnValue(array($callback_container, __FUNCTION__)));

    $this->assertSame($callback_label, $this->entity->label());
  }
}
