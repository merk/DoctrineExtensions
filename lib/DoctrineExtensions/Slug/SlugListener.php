<?php
/**
 * DoctrineExtensions Slug
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace DoctrineExtensions\Slug;
use \Doctrine\Common\EventSubscriber,
    \Doctrine\Common\Annotations\AnnotationReader,
    \Doctrine\ORM\EntityManager,
    \Doctrine\ORM\Events,
    \Doctrine\ORM\Event\OnFlushEventArgs;

class SlugListener implements EventSubscriber
{
    protected $_reader;

    protected $_metadata = array();
    protected $_fieldNames = array();
    protected $_annotations = array();

    public function __construct(AnnotationReader $reader)
    {
        $this->_reader = $reader;
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush);
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $entities = array();
        foreach ($uow->getScheduledEntityUpdates() AS $entity)
            $entities[] = $entity;

        foreach ($uow->getScheduledEntityInserts() AS $entity)
            $entities[] = $entity;

        foreach ($entities AS $entity)
        {
            $class = get_class($entity);
            if (!array_key_exists($class, $this->_metadata)))
            {
                $this->_metadata[$class] = $em->getClassMetadata($class);

                $reflClass = new \ReflectionClass($class);
                foreach ($reflClass->getProperties() AS $property)
                {
                    $slugAnnot = $this->_reader->getPropertyAnnotation($property, 'DoctrineExtensions\Slug\Annotation');

                    if ($slugAnnot)
                    {
                        $this->_fieldNames[$class][] = $property->getName();
                        $this->_annotations[$class] = $slugAnnot;
                    }
                }

                if (!count($this->_fieldNames[$class]))
                {
                    $this->_fieldNames[$class] = array();
                    $this->_annotations[$class] = false;
                }
            }

            $annot = $this->_annotations[$class];
            $metadata = $this->_metadata[$class];
            foreach ($this->_fieldNames[$class] AS $slugField)
            {
                // TODO: Re-calculating the slug every time an entity is saved is inefficient
                // Hopefully the UOW knows what has changed by this point?

                $slugFields = array();
                foreach ($annot->fields AS $field)
                    $slugFields[] = $metadata->getFieldValue($entity, $field);

                $slug = Helper::slug(
                    implode($annot->fieldSeparator, $slugFields),
                    $annot->spaceChar,
                    $annot->maxLength,
                    $annot->replaceRegex,
                    $annot->iconv
                );

                if ($slug != $metadata->getFieldValue($entity, $slugField))
                {
                    if ($annot->unique)
                    {
                        $qb = $em->createQueryBuilder();
                        $qb->select('COUNT(e)');
                        $qb->from($class, 'e');
                        $qb->where("e.{$slugField} = ?1");
                        $qb->setParameter(1, $slug);

                        if ($em->createQuery($db)->getSingleScalarResult())
                        {
                            $id = implode(',', (array) $metadata->getIdentifierValues($entity));
                            Exception::slugAlreadyExists($class, $id, $slug);
                        }
                    }

                    $metadata->setFieldValue($entity, $slugField, $slug);
            }
        }
    }
}
