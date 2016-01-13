<?php

namespace ArcoVoltaico\ImportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ArcoVoltaico\ImportBundle\Entity\Import;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ArcoVoltaico\ImportBundle\Form\ImportType;
use ArcoVoltaico\ImportBundle\Entity\Loc1;
use ArcoVoltaico\ImportBundle\Entity\Inmueble;
use ArcoVoltaico\ImportBundle\Entity\Foto;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

/**
 * Import controller.
 *
 */
class ImportController extends Controller {

    /**
     * Lists all Import entities.
     *
     */
    public function indexAction($venue = null, $page = 1) {
        $tr = 'translator';
        $em = $this->getDoctrine()->getManager();

        $result = $em->getRepository('ArcoVoltaicoImportBundle:Import')->findAll();
        $adapter = new ArrayAdapter($result);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);

        try {
            $pager->setCurrentPage($page);
        } catch (NotValidCurrentPageException $err) {
            throw new NotFoundHttpException();
        }
        $columns = array();


        return $this->render('@crud/list.html.twig', array(
                    'entities' => $pager,
                    'title' => 'import',
                    'headtitle' => 'list',
                    'venue' => $venue,
                    'columns' => $columns
        ));
    }

    /**
     * Displays a form to create a new Import entity.
     *
     */
    public function newAction(Request $request, $venue = null) {
        $entity = new Import();
        $form = $this->createForm(new ImportType, $entity);

        $tr = 'translator';
        if ($request->getMethod() == 'POST') {
            $form->bind($request); //now the $enquiry object holds a representation of what the user submitted
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                $em->flush();


                return $this->redirectToRoute('importing', array('id' => $entity->getId()));
//return $this->redirectToRoute('import_show', array('id' => $entity->getId(),'venue'=>$venue));
            } else {
                $this->addFlash('notification', array(
                    'class' => 'danger',
                    'title' => 'Upsss...',
                    'message' => 'form_error'
                        )
                );
            }
        }


        return $this->render('@crud/new.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
                    'title' => 'import',
                    'headtitle' => 'new',
                    'venue' => $venue
        ));
    }

    /**
     * Finds and displays a Import entity.
     *
     */
    public function showAction($id, $venue = null) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ArcoVoltaicoImportBundle:Import')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Import entity.');
        }


        $form = $this->createForm(new ImportType, $entity);

        return $this->render('@crud/show.html.twig', array(
                    'entity' => $entity,
                    'title' => 'import',
                    'headtitle' => 'details',
                    'venue' => $venue,
                    'newlink' => true, //false if the entity is dependent of other (ex:offer depends from product)
                    'form' => $form->createView()));
    }

    /**
     * Displays a form to edit an existing Import entity.
     *
     */
    public function editAction($id, Request $request, $venue = null) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ArcoVoltaicoImportBundle:Import')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Import entity.');
        }


        $object = "";
        $form = $this->createForm(new ImportType, $entity);

        if ($request->getMethod() == 'POST') {
            $form->bind($request); //now the $enquiry object holds a representation of what the user submitted
            if ($form->isValid()) {
                $data = $form->getData();
                $em->flush();

                $this->addFlash('notification', array(
                    'class' => 'info',
                    'title' => 'confirmation',
                    'message' => 'record_updated'
                        )
                );
                return $this->redirectToRoute('import_show', array('id' => $id, 'venue' => $venue));
            } else {
                $this->addFlash('notification', array(
                    'class' => 'danger',
                    'title' => 'Upsss...',
                    'message' => 'form_error'
                        )
                );
            }
        }
        $deleteForm = $this->createDeleteForm($id, $venue);


        return $this->render('@crud/edit.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
                    'delete_form' => $deleteForm->createView(),
                    'title' => 'import',
                    'headtitle' => 'edit',
                    'venue' => $venue
        ));
    }

    /**
     * Deletes a Import entity.
     *
     */
    public function deleteAction(Request $request, $id, $venue = null) {
        $form = $this->createDeleteForm($id, $venue);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ArcoVoltaicoImportBundle:Import')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Import entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirectToRoute('import', array('venue' => $venue));
    }

    /**
     * Creates a form to delete a Import entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id, $venue) {
        $tr = $this->get('translator');
        $warning = $tr->trans('del_sure');
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('import_delete', array('id' => $id, 'venue' => $venue)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array('label' => 'Delete', 'attr' => array('onclick' => "return confirm('" . $warning . "')", 'class' => 'btn btn-danger')))
                        ->getForm()
        ;
    }

    public function importingAction($id = null) {
        ini_set('memory_limit','1536M'); // 1.5 GB
ini_set('max_execution_time', 18000); // 5 hours
//
        //?upload=false /uploading photos file or just create the record with the url
        if (!$id) {
            throw new NotFoundHttpException('No XML');
        }
        
        $importer = $this->get('importer');
        $xml_entity = 'ArcoVoltaicoImportBundle:Import';
        $msg = $importer->run($xml_entity, $id);
//        $em = $this->getDoctrine()->getManager();
//        $em->flush();
      
        return $this->render('general/msg.html.twig', array(
                    'msg' => $msg
        ));
    }
}