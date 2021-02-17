<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Author;
use App\Entity\Book;

class BookController extends AbstractController
{
    /**
     * @Route("/book", name="book_index", methods={"GET"})
     */
    public function index(Request $r): Response
    {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $filterByAuthorId = $r->query->get('author_id');

        $authors = $this->getDoctrine()->getRepository(Author::class)->findAll();
        $books = $this->getDoctrine()->getRepository(Book::class);

        $filterBy = [];

        if (null !== $filterByAuthorId && "-1" !== $filterByAuthorId) {
            $filterBy = ['author_id' => $r->query->get('author_id')];
        }
        else {
            // $books = $books->findAll();
        }

        if ('title_az' == $r->query->get('sort'))  {
            $books = $books->findBy($filterBy, ['title'=>'asc']);
        }
        elseif ('title_za' == $r->query->get('sort'))  {
            $books = $books->findBy($filterBy, ['title'=>'desc']);
        }
        elseif ('pages_az' == $r->query->get('sort'))  {
            $books = $books->findBy($filterBy, ['pages'=>'asc']);
        }
        elseif ('pages_za' == $r->query->get('sort'))  {
            $books = $books->findBy($filterBy, ['pages'=>'desc']);
        }
        else {
            if ($filterBy === []) {
                $books = $books->findAll();
            } else {
                $books = $books->findBy($filterBy);
            }
        }
        
        return $this->render('book/index.html.twig', [
            'books' => $books,
            'authors' => $authors,
            'authorId' => $r->query->get('author_id') ?? 0,
            'sortBy' => $r->query->get('sort') ?? 'default',
            'success' => $r->getSession()->getFlashBag()->get('success', [])
        ]);
    }

    /**
     * @Route("/book/create", name="book_create", methods={"GET"})
     */
    public function create(Request $r): Response
    {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $book_title = $r->getSession()->getFlashBag()->get('book_title', []);
        $book_pages = $r->getSession()->getFlashBag()->get('book_pages', []);
        $book_isbn = $r->getSession()->getFlashBag()->get('book_isbn', []);
        $book_short_description = $r->getSession()->getFlashBag()->get('book_short_description', []);

        $authors = $this->getDoctrine()->getRepository(Author::class)->findBy([],['surname'=>'asc']);
        
        return $this->render('book/create.html.twig', [
            'authors' => $authors,
            'errors' => $r->getSession()->getFlashBag()->get('errors', []),
            'book_title' => $book_title[0] ?? '',
            'book_pages' => $book_pages[0] ?? '',
            'book_isbn' => $book_isbn[0] ?? '',
            'book_short_description' => $book_short_description[0] ?? ''
        ]);
    }

    /**
     * @Route("/book/store", name="book_store", methods={"POST"})
     */
    public function store(Request $r, ValidatorInterface $validator): Response
    {
        $author = $this->getDoctrine()->getRepository(Author::class)->find($r->request->get('book_author_id'));

        $book = new Book();
        $book->
        setTitle($r->request->get('book_title'))->
        setPages((int)$r->request->get('book_pages'))->
        setIsbn($r->request->get('book_isbn'))->
        setShortDescription($r->request->get('book_short_description'))->
        setAuthor($author);
       
        $errors = $validator->validate($book);

        if (count($errors) > 0) {

            foreach($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            $r->getSession()->getFlashBag()->add('book_title', $r->request->get('book_title'));
            $r->getSession()->getFlashBag()->add('book_pages', $r->request->get('book_pages'));
            $r->getSession()->getFlashBag()->add('book_isbn', $r->request->get('book_isbn'));
            $r->getSession()->getFlashBag()->add('book_short_description', $r->request->get('book_short_description'));

            return $this->redirectToRoute('book_create');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'book added.');

        return $this->redirectToRoute('book_index');
    }

    /**
     * @Route("/book/edit/{id}", name="book_edit", methods={"GET"})
     */
    public function edit(int $id, Request $r): Response
    {
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        $authors = $this->getDoctrine()->getRepository(Author::class)->findAll();

        $book_title = $r->getSession()->getFlashBag()->get('book_title', []);
        $book_pages = $r->getSession()->getFlashBag()->get('book_pages', []);
        $book_isbn = $r->getSession()->getFlashBag()->get('book_isbn', []);
        $book_short_description = $r->getSession()->getFlashBag()->get('book_short_description', []);

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'authors' => $authors,
            'errors' => $r->getSession()->getFlashBag()->get('errors', []),
            'book_title' => $book_title[0] ?? '',
            'book_pages' => $book_pages[0] ?? '',
            'book_isbn' => $book_isbn[0] ?? '',
            'book_short_description' => $book_short_description[0] ?? ''
        ]);
    }

    /**
    * @Route("/book/update/{id}", name="book_update", methods={"POST"})
    */
    public function update(Request $r, $id, ValidatorInterface $validator): Response
    {
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);
        $author = $this->getDoctrine()->getRepository(Author::class)->find($r->request->get('books_author'));

        $book->
        setTitle($r->request->get('book_title'))->
        setPages((int)$r->request->get('book_pages'))->
        setIsbn($r->request->get('book_isbn'))->
        setShortDescription($r->request->get('book_short_description'))->
        setAuthor($author);
        $errors = $validator->validate($book);

        if (count($errors) > 0) {

            foreach($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            $r->getSession()->getFlashBag()->add('book_title', $r->request->get('book_title'));
            $r->getSession()->getFlashBag()->add('book_pages', $r->request->get('book_pages'));
            $r->getSession()->getFlashBag()->add('book_isbn', $r->request->get('book_isbn'));
            $r->getSession()->getFlashBag()->add('book_short_description', $r->request->get('book_short_description'));

            return $this->redirectToRoute('book_create');
        }
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'Book edited.');

        return $this->redirectToRoute('book_index');
    }

    /**
    * @Route("/book/delete/{id}", name="book_delete", methods={"POST"})
    */
    public function delete($id): Response
    {
        $book = $this->getDoctrine()->getRepository(Book::class)->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($book);
        $entityManager->flush();

        return $this->redirectToRoute('book_index');
    }

}