<?php
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection AccessModifierPresentedInspection */

namespace Czim\JsonApi\Test\Integration\Encoding;

use Czim\JsonApi\Contracts\Repositories\ResourceCollectorInterface;
use Czim\JsonApi\Contracts\Repositories\ResourceRepositoryInterface;
use Czim\JsonApi\Encoder\Encoder;
use Czim\JsonApi\Encoder\Factories\TransformerFactory;
use Czim\JsonApi\Encoder\Transformers\ModelTransformer;
use Czim\JsonApi\Repositories\ResourceRepository;
use Czim\JsonApi\Support\Validation\JsonApiValidator;
use Czim\JsonApi\Test\AbstractSeededTestCase;
use Czim\JsonApi\Test\Helpers\Models\TestAuthor;
use Czim\JsonApi\Test\Helpers\Models\TestComment;
use Czim\JsonApi\Test\Helpers\Models\TestPost;
use Czim\JsonApi\Test\Helpers\Models\TestSeo;
use Czim\JsonApi\Test\Helpers\Resources\TestAuthorResource;
use Czim\JsonApi\Test\Helpers\Resources\TestCommentResource;
use Czim\JsonApi\Test\Helpers\Resources\TestPostResource;
use Czim\JsonApi\Test\Helpers\Resources\TestPostResourceWithDefaults;
use Czim\JsonApi\Test\Helpers\Resources\TestSeoResource;
use Illuminate\Support\Collection;
use Mockery;
use RuntimeException;

/**
 * Class ModelEncodingTest
 *
 * @group encoding
 */
class ModelEncodingTest extends AbstractSeededTestCase
{

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('jsonapi.repository.resource.namespace', 'Czim\\JsonApi\\Test\\Helpers\\Resources\\');
        $this->app['config']->set('jsonapi.transform.links.related-segment', '');
    }

    /**
     * @test
     */
    function it_transforms_a_model_with_related_records()
    {
        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, new TestPostResource);
        $repository->register(TestComment::class, new TestCommentResource);
        $repository->register(TestAuthor::class, new TestAuthorResource);
        $repository->register(TestSeo::class, new TestSeoResource);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/1/comments',
                            ],
                            'data' => [
                                ['id' => '1', 'type' => 'test-comments'],
                                ['id' => '2', 'type' => 'test-comments'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/1/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => '1'],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/1/seo',
                            ],
                            'data' => null,
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/related',
                                'related' => 'http://localhost/api/test-posts/1/related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/pivot-related',
                                'related' => 'http://localhost/api/test-posts/1/pivot-related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
            $transformer->transform(TestPost::first())
        );
    }

    /**
     * @test
     */
    function it_transforms_a_model_with_related_records_with_configured_relation_link_segments()
    {
        $this->app['config']->set('jsonapi.transform.links.relationships-segment', '');
        $this->app['config']->set('jsonapi.transform.links.related-segment', 'related');

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, new TestPostResource);
        $repository->register(TestComment::class, new TestCommentResource);
        $repository->register(TestAuthor::class, new TestAuthorResource);
        $repository->register(TestSeo::class, new TestSeoResource);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/comments',
                                'related' => 'http://localhost/api/test-posts/1/related/comments',
                            ],
                            'data' => [
                                ['id' => '1', 'type' => 'test-comments'],
                                ['id' => '2', 'type' => 'test-comments'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/main-author',
                                'related' => 'http://localhost/api/test-posts/1/related/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => '1'],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/seo',
                                'related' => 'http://localhost/api/test-posts/1/related/seo',
                            ],
                            'data' => null,
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/related',
                                'related' => 'http://localhost/api/test-posts/1/related/related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/pivot-related',
                                'related' => 'http://localhost/api/test-posts/1/related/pivot-related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
            $transformer->transform(TestPost::first())
        );
    }

    /**
     * @test
     */
    function it_transforms_a_model_with_empty_relations_data()
    {
        // Set up the model to clear relations.
        $model = TestPost::find(2);
        $model->test_author_id = null;
        $model->save();


        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, TestPostResource::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '2',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Elaborate Alternative Title',
                        'body'                 => 'Donec nec metus urna. Tosti pancake frying pan tortellini Fusce ex massa.',
                        'type'                 => 'news',
                        'checked'              => false,
                        'description-adjusted' => 'Prefix: some alternative testing post',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/2/comments',
                            ],
                            'data' => [],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/2/main-author',
                            ],
                            'data' => null,
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/2/seo',
                            ],
                            'data' => null,
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/related',
                                'related' => 'http://localhost/api/test-posts/2/related',
                            ],
                            'data' => [],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/pivot-related',
                                'related' => 'http://localhost/api/test-posts/2/pivot-related',
                            ],
                            'data' => [],
                        ],
                    ],
                ],
            ],
            $transformer->transform($model)
        );
    }

    /**
     * @test
     */
    function it_transforms_a_model_with_eager_loaded_data()
    {
        $model = TestPost::first();
        $model->load('comments', 'author', 'related', 'pivotRelated');


        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, TestPostResource::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/1/comments',
                            ],
                            'data' => [
                                ['id' => '1', 'type' => 'test-comments'],
                                ['id' => '2', 'type' => 'test-comments'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/1/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => 1],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/1/seo',
                            ],
                            'data' => null,
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/related',
                                'related' => 'http://localhost/api/test-posts/1/related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/pivot-related',
                                'related' => 'http://localhost/api/test-posts/1/pivot-related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
            $transformer->transform($model)
        );
    }

    /**
     * @test
     */
    function it_throws_an_exception_if_no_resource_is_registered_for_a_referenced_related_model()
    {
        $this->expectException(RuntimeException::class);

        // Add seo for model
        $model = TestPost::find(2);
        $model->test_author_id = null;
        $model->save();

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, TestPostResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        $encoder->encode($model);
    }


    // ------------------------------------------------------------------------------
    //      Morph Relation
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_transforms_a_model_with_an_empty_morph_to_relation()
    {
        $model = TestSeo::create(['slug' => 'orphan']);

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-seos',
                    'attributes' => [
                        'slug' => 'orphan',
                    ],
                    'relationships' => [
                        'seoable' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-seos/1/relationships/seoable',
                                'related' => 'http://localhost/api/test-seos/1/seoable',
                            ],
                            'data' => null,
                        ],
                    ],
                ],
            ],
            $transformer->transform($model)
        );
    }

    // ------------------------------------------------------------------------------
    //      Sideloaded includes
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_transforms_a_model_with_sideloaded_includes()
    {
        // Add seo for model
        $model = TestPost::first();
        $seo = new TestSeo(['slug' => 'testing post 1']);
        $model->seo()->save($seo);

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        // Set the request for includes
        $encoder->setRequestedIncludes(['comments', 'main-author', 'seo']);

        $repository->register(TestPost::class, TestPostResource::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        $data = $encoder->encode($model);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/1/comments',
                            ],
                            'data' => [
                                ['type' => 'test-comments', 'id' => '1'],
                                ['type' => 'test-comments', 'id' => '2'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/1/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => 1],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/1/seo',
                            ],
                            'data' => ['type' => 'test-seos', 'id' => '1'],
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/related',
                                'related' => 'http://localhost/api/test-posts/1/related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/pivot-related',
                                'related' => 'http://localhost/api/test-posts/1/pivot-related',
                            ],
                            'data' => [
                                ['type' => 'test-posts', 'id' => '2'],
                                ['type' => 'test-posts', 'id' => '3'],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id'         => '1',
                        'type'       => 'test-comments',
                        'attributes' => [
                            'title'       => 'Comment Title A',
                            'body'        => 'Lorem ipsum dolor sit amet.',
                            'description' => 'comment one',
                        ],
                        'relationships' => [
                            'author' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/1/relationships/author',
                                    'related' => 'http://localhost/api/test-comments/1/author',
                                ],
                                'data' => ['type' => 'test-authors', 'id' => '2'],
                            ],
                            'post' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/1/relationships/post',
                                    'related' => 'http://localhost/api/test-comments/1/post',
                                ],
                                'data' => ['type' => 'test-posts', 'id' => '1'],
                            ],
                            'seos' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/1/relationships/seos',
                                    'related' => 'http://localhost/api/test-comments/1/seos',
                                ],
                                'data' => [],
                            ],
                        ],

                    ],
                    [
                        'id'         => '2',
                        'type'       => 'test-comments',
                        'attributes' => [
                            'title'       => 'Comment Title B',
                            'body'        => 'Phasellus iaculis velit nec purus rutrum eleifend.',
                            'description' => 'comment two',
                        ],
                        'relationships' => [
                            'author' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/2/relationships/author',
                                    'related' => 'http://localhost/api/test-comments/2/author',
                                ],
                                'data' => ['type' => 'test-authors', 'id' => '2'],
                            ],
                            'post' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/2/relationships/post',
                                    'related' => 'http://localhost/api/test-comments/2/post',
                                ],
                                'data' => ['type' => 'test-posts', 'id' => '1'],
                            ],
                            'seos' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-comments/2/relationships/seos',
                                    'related' => 'http://localhost/api/test-comments/2/seos',
                                ],
                                'data' => [],
                            ],
                        ],
                    ],
                    [
                        'id'         => '1',
                        'type'       => 'test-authors',
                        'attributes' => [
                            'name' => 'Test Testington',
                        ],
                        'relationships' => [
                            'posts' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-authors/1/relationships/posts',
                                    'related' => 'http://localhost/api/test-authors/1/posts',
                                ],
                                'data' => [
                                    ['type' => 'test-posts', 'id' => '1'],
                                    ['type' => 'test-posts', 'id' => '2'],
                                ],
                            ],
                            'comments' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-authors/1/relationships/comments',
                                    'related' => 'http://localhost/api/test-authors/1/comments',
                                ],
                                'data' => [
                                    ['type' => 'test-comments', 'id' => '3']
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'         => '1',
                        'type'       => 'test-seos',
                        'attributes' => [
                            'slug' => 'testing post 1',
                        ],
                        'relationships' => [
                            'seoable' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-seos/1/relationships/seoable',
                                    'related' => 'http://localhost/api/test-seos/1/seoable'
                                ],
                                'data' => ['type' => 'test-posts', 'id' => '1'],
                            ],
                        ],
                    ],
                ],
            ],
            $data
        );

        static::assertTrue(
            (new JsonApiValidator)->validateSchema($data),
            'Generated array does not match JSON-API Schema'
        );
    }

    /**
     * @test
     */
    function it_transforms_a_model_with_sideloaded_includes_for_empty_relations_data()
    {
        // Clear the author relation
        $model = TestPost::find(2);
        $model->test_author_id = null;
        $model->save();

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        // Set the request for includes
        $encoder->setRequestedIncludes(['comments', 'main-author', 'seo']);

        $repository->register(TestPost::class, TestPostResource::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        $data = $encoder->encode($model);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '2',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Elaborate Alternative Title',
                        'body'                 => 'Donec nec metus urna. Tosti pancake frying pan tortellini Fusce ex massa.',
                        'type'                 => 'news',
                        'checked'              => false,
                        'description-adjusted' => 'Prefix: some alternative testing post',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/2/comments',
                            ],
                            'data' => [],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/2/main-author',
                            ],
                            'data' => null,
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/2/seo',
                            ],
                            'data' => null,
                        ],
                        'related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/related',
                                'related' => 'http://localhost/api/test-posts/2/related',
                            ],
                            'data' => [],
                        ],
                        'pivot-related' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/2/relationships/pivot-related',
                                'related' => 'http://localhost/api/test-posts/2/pivot-related',
                            ],
                            'data' => [],
                        ],
                    ],
                ],
            ],
            $data
        );

        static::assertTrue(
            (new JsonApiValidator)->validateSchema($data),
            'Generated array does not match JSON-API Schema'
        );
    }


    // ------------------------------------------------------------------------------
    //      Defaults vs. requested includes
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_transforms_using_default_relations_set_in_a_resource()
    {
        // Add seo for model
        $model = TestPost::first();
        $seo = new TestSeo(['slug' => 'testing post 1']);
        $model->seo()->save($seo);

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, TestPostResourceWithDefaults::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        $data = $encoder->encode($model);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/1/comments',
                            ],
                            'data' => [
                                ['type' => 'test-comments', 'id' => '1'],
                                ['type' => 'test-comments', 'id' => '2'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/1/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => 1],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/1/seo',
                            ],
                            'data' => ['type' => 'test-seos', 'id' => '1'],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id'         => '1',
                        'type'       => 'test-authors',
                        'attributes' => [
                            'name' => 'Test Testington',
                        ],
                        'relationships' => [
                            'posts' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-authors/1/relationships/posts',
                                    'related' => 'http://localhost/api/test-authors/1/posts',
                                ],
                                'data' => [
                                    ['type' => 'test-posts', 'id' => '1'],
                                    ['type' => 'test-posts', 'id' => '2'],
                                ],
                            ],
                            'comments' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-authors/1/relationships/comments',
                                    'related' => 'http://localhost/api/test-authors/1/comments',
                                ],
                                'data' => [
                                    ['type' => 'test-comments', 'id' => '3']
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'         => '1',
                        'type'       => 'test-seos',
                        'attributes' => [
                            'slug' => 'testing post 1',
                        ],
                        'relationships' => [
                            'seoable' => [
                                'links' => [
                                    'self'    => 'http://localhost/api/test-seos/1/relationships/seoable',
                                    'related' => 'http://localhost/api/test-seos/1/seoable'
                                ],
                                'data' => ['type' => 'test-posts', 'id' => '1'],
                            ],
                        ],
                    ],
                ],
            ],
            $data
        );
    }

    /**
     * @test
     */
    function it_transforms_ignoring_default_resource_relations_if_requested_includes_set_and_configured_to()
    {
        $this->app['config']->set('jsonapi.transform.requested-includes-cancel-defaults', true);

        // Add seo for model
        $model = TestPost::first();

        /** @var ResourceCollectorInterface|Mockery\Mock $collector */
        $collector = Mockery::mock(ResourceCollectorInterface::class);
        $collector->shouldReceive('collect')->andReturn(new Collection);

        $factory    = new TransformerFactory;
        $repository = new ResourceRepository($collector);
        $encoder    = new Encoder($factory, $repository);
        $this->app->instance(ResourceRepositoryInterface::class, $repository);

        $repository->register(TestPost::class, TestPostResourceWithDefaults::class);
        $repository->register(TestComment::class, TestCommentResource::class);
        $repository->register(TestAuthor::class, TestAuthorResource::class);
        $repository->register(TestSeo::class, TestSeoResource::class);

        $encoder->setRequestedIncludes(['seo']);

        $transformer = new ModelTransformer;
        $transformer->setEncoder($encoder);

        $data = $encoder->encode($model);

        static::assertEquals(
            [
                'data' => [
                    'id'         => '1',
                    'type'       => 'test-posts',
                    'attributes' => [
                        'title'                => 'Some Basic Title',
                        'body'                 => 'Lorem ipsum dolor sit amet, egg beater batter pan consectetur adipiscing elit.',
                        'type'                 => 'notice',
                        'checked'              => true,
                        'description-adjusted' => 'Prefix: the best possible post for testing',
                    ],
                    'relationships' => [
                        'comments' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/comments',
                                'related' => 'http://localhost/api/test-posts/1/comments',
                            ],
                            'data' => [
                                ['type' => 'test-comments', 'id' => '1'],
                                ['type' => 'test-comments', 'id' => '2'],
                            ],
                        ],
                        'main-author' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/main-author',
                                'related' => 'http://localhost/api/test-posts/1/main-author',
                            ],
                            'data' => ['type' => 'test-authors', 'id' => 1],
                        ],
                        'seo' => [
                            'links' => [
                                'self'    => 'http://localhost/api/test-posts/1/relationships/seo',
                                'related' => 'http://localhost/api/test-posts/1/seo',
                            ],
                            'data' => null,
                        ],
                    ],
                ],
            ],
            $data
        );
    }

}
