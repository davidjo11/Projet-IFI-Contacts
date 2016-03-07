<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Contact;
use AppBundle\Form\ContactType;

/**
 * Contact controller.
 *
 * @Route("/")
 */
class ContactController extends Controller
{
    /**
     * Lists all Contact entities.
     *
     * @Route("/", name="_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get("security.token_storage")->getToken()->getUser();

        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from('AppBundle:Contact','c')
            ->where('c.user=?1')
                ->setParameter(1,$user);

        $pagination = $this->get('knp_paginator')->paginate(
            $qb->getQuery(), 
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('contact/index.html.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     * Creates a new Contact entity.
     *
     * @Route("/new", name="_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $contact = new Contact();
        $user = $this->container->get("security.token_storage")->getToken()->getUser();

        $form = $this->createForm(new ContactType(), $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $contact->setUser($user);
            $em->persist($contact);
            $em->flush();

            //Sending mail to administrator
            $mailer = $this->get('mailer');
            $template = 'mail/added.html.twig';
            $message = \Swift_Message::newInstance()
                ->setSubject('Un nouveau contact a été ajouté!')
                ->setFrom('no-reply@localhost')
                ->setTo('david.josias@etudiant.univ-lille1.fr')
                ->setBody(
                    $this->renderView(
                        $template,
                        array('name' => $contact->getPrenom().' '.strtoupper($contact->getNom()),
                        )
                    ),
                    'text/html'
                )
            ;
            $mailer->send($message);

            return $this->redirectToRoute('_show', array('id' => $contact->getId()));
        }

        return $this->render('contact/form.html.twig', array(
            'contact' => $contact,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Contact entity.
     *
     * @Route("/{id}", name="_show", requirements={"id" = "\d+"})
     * @Method("GET")
     */
    public function showAction(Contact $contact)
    {
        $deleteForm = $this->createDeleteForm($contact);

        return $this->render('contact/show.html.twig', array(
            'contact' => $contact,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Contact entity.
     *
     * @Route("/{id}/edit", name="_edit", requirements={"id" = "\d+"})
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Contact $contact)
    {
        $editForm = $this->createForm(new ContactType(), $contact);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();

            return $this->redirectToRoute('_show', array('id' => $contact->getId()));
        }

        return $this->render('contact/form.html.twig', array(
            'contact' => $contact,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Contact entity.
     *
     * @Route("/{id}", name="_delete", requirements={"id" = "\d+"})
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Contact $contact)
    {
        $form = $this->createDeleteForm($contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($contact);
            $em->flush();
        }

        return $this->redirectToRoute('_index');
    }

    /**
     * Creates a form to delete a Contact entity.
     *
     * @param Contact $contact The Contact entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Contact $contact)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('_delete', array('id' => $contact->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
