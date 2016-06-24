<?php

namespace ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;

use ApiBundle\Repository\TaskRepository;
use ApiBundle\Entity\Task;

abstract class ApiController extends Controller
{
    private $headers = [
        'Content-Type' => 'application/json; charset: utf-8',
    ];

    protected abstract function getModel();

    protected function getFullModelName()
    {
        return 'ApiBundle:' . $this->getModel();
    }

    protected function getModelClassName()
    {
        return 'ApiBundle\\Entity\\' . $this->getModel();
    }

    public function __call($method, $args)
    {
        try {
            $method = 'do' . $method; // Method name case does not matter in php
            return call_user_func_array([$this, $method], $args);
        } catch (\LogicException $ex) { // TODO: use custom exception
            return new JsonResponse(
                ['error' => $ex->getMessage()],
                $ex->getCode(),
                $this->headers
            );
        } catch (\Exception $ex) {
            if ($this->get('kernel')->isDebug()) {
                throw $ex;
            }

            return new JsonResponse(
                ['error' => 'Oops! something went wrong, please try again later'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $this->headers
            );
        }
    }

    protected function getSerializer()
    {
        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(array('createdAt', 'updatedAt'));

        return new Serializer([$normalizer], [new JsonEncoder()]);
    }

    private function doList()
    {
        $page = $this->getRequest()->get('page', 1);
        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository($this->getFullModelName())->getFindAllQuery(true);
        $qb->orderBy($qb->getRootAlias() . '.id', 'DESC');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            $this->container->getParameter('items_per_page')
        );

        $data = $this->getSerializer()->serialize($pagination->getItems() , 'json');

        return new Response($data, Response::HTTP_OK, $this->headers + [
            'X-Pagination-Page' => $pagination->getCurrentPageNumber(),
            'X-Pagination-Pages' => $pagination->getPageCount(),
            'X-Pagination-Total' => $pagination->getTotalItemCount()
        ]);
    }

    protected function saveModel($data, Task $task = null)
    {
        $options = [];

        if ($task) {
            $options['object_to_populate'] = $task;
        }

        $task = $this->getSerializer()->deserialize($data, $this->getModelClassName(), 'json', $options);

        if ($task->getStatus() && !in_array($task->getStatus(), [Task::STATUS_PENDING, Task::STATUS_DONE])) {
            throw new \UnexpectedValueException('Invalid value for Status field', Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        return $task;
    }

    private function doCreate()
    {
        $data = $this->getRequest()->getContent();
        $task = $this->saveModel($data);
        $data = $this->getSerializer()->serialize($task, 'json');

        return new Response($data, Response::HTTP_CREATED, $this->headers);
    }

    protected function getItem($id)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->find($this->getFullModelName(), $id);

        if (!$task) {
            throw new \LogicException('Not found', Response::HTTP_NOT_FOUND);
        }

        return $task;
    }

    private function doEdit($id)
    {
        $data = $this->getRequest()->getContent();
        $task = $this->saveModel($data, $this->getItem($id));
        $data = $this->getSerializer()->serialize($task, 'json');

        return new Response($data, Response::HTTP_OK, $this->headers);
    }

    private function doDelete($id)
    {
        $item = $this->getItem($id);
        $em = $this->getDoctrine()->getManager();

        $em->remove($item);
        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT, $this->headers);
    }
}

