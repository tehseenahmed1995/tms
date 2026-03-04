<?php

use App\Models\Translation;
use App\Models\Tag;
use App\Repositories\TranslationRepository;
use App\DataTransferObjects\SearchCriteriaData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new TranslationRepository();
});

test('creates translation with tags', function () {
    $data = [
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ];
    $tags = ['web', 'mobile'];

    $translation = $this->repository->create($data, $tags);

    expect($translation)->toBeInstanceOf(Translation::class)
        ->and($translation->key)->toBe('auth.login.title')
        ->and($translation->locale)->toBe('en')
        ->and($translation->content)->toBe('Login')
        ->and($translation->tags)->toHaveCount(2)
        ->and($translation->tags->pluck('name')->toArray())->toBe(['web', 'mobile']);
});

test('creates translation without tags', function () {
    $data = [
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ];

    $translation = $this->repository->create($data, []);

    expect($translation->tags)->toHaveCount(0);
});

test('updates translation and syncs tags', function () {
    $translation = Translation::factory()->create();
    $translation->tags()->attach(Tag::factory()->create(['name' => 'web']));

    $data = [
        'key' => 'auth.logout.title',
        'locale' => 'fr',
        'content' => 'Déconnexion',
    ];
    $tags = ['mobile', 'desktop'];

    $updated = $this->repository->update($translation, $data, $tags);

    expect($updated->key)->toBe('auth.logout.title')
        ->and($updated->locale)->toBe('fr')
        ->and($updated->content)->toBe('Déconnexion')
        ->and($updated->tags)->toHaveCount(2)
        ->and($updated->tags->pluck('name')->toArray())->toContain('mobile', 'desktop');
});

test('deletes translation and detaches tags', function () {
    $translation = Translation::factory()->create();
    $tag = Tag::factory()->create();
    $translation->tags()->attach($tag);

    $result = $this->repository->delete($translation);

    expect($result)->toBeTrue()
        ->and(Translation::find($translation->id))->toBeNull()
        ->and($tag->fresh()->translations)->toHaveCount(0);
});

test('finds translation by id with tags', function () {
    $translation = Translation::factory()->create();
    $tag = Tag::factory()->create();
    $translation->tags()->attach($tag);

    $found = $this->repository->findById($translation->id);

    expect($found)->toBeInstanceOf(Translation::class)
        ->and($found->id)->toBe($translation->id)
        ->and($found->tags)->toHaveCount(1);
});

test('searches translations by key', function () {
    Translation::factory()->create(['key' => 'auth.login.title']);
    Translation::factory()->create(['key' => 'auth.logout.title']);
    Translation::factory()->create(['key' => 'common.save.button']);

    $criteria = new SearchCriteriaData(key: 'auth');
    $results = $this->repository->search($criteria);

    expect($results->total())->toBe(2);
});

test('searches translations by locale', function () {
    Translation::factory()->create(['locale' => 'en']);
    Translation::factory()->create(['locale' => 'en']);
    Translation::factory()->create(['locale' => 'fr']);

    $criteria = new SearchCriteriaData(locale: 'en');
    $results = $this->repository->search($criteria);

    expect($results->total())->toBe(2);
});

test('searches translations by content', function () {
    Translation::factory()->create(['content' => 'Login to your account']);
    Translation::factory()->create(['content' => 'Logout from system']);
    Translation::factory()->create(['content' => 'Save changes']);

    $criteria = new SearchCriteriaData(content: 'Login');
    $results = $this->repository->search($criteria);

    expect($results->total())->toBe(1);
});

test('searches translations by tags', function () {
    $webTag = Tag::factory()->create(['name' => 'web']);
    $mobileTag = Tag::factory()->create(['name' => 'mobile']);
    
    $translation1 = Translation::factory()->create();
    $translation1->tags()->attach([$webTag->id, $mobileTag->id]);
    
    $translation2 = Translation::factory()->create();
    $translation2->tags()->attach([$webTag->id]);
    
    Translation::factory()->create(); // No tags

    $criteria = new SearchCriteriaData(tags: ['web', 'mobile']);
    $results = $this->repository->search($criteria);

    expect($results->total())->toBe(1);
});

test('gets translations by locale', function () {
    Translation::factory()->create(['locale' => 'en', 'key' => 'auth.login', 'content' => 'Login']);
    Translation::factory()->create(['locale' => 'en', 'key' => 'auth.logout', 'content' => 'Logout']);
    Translation::factory()->create(['locale' => 'fr', 'key' => 'auth.login', 'content' => 'Connexion']);

    $results = $this->repository->getByLocale('en');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('key')->toArray())->toContain('auth.login', 'auth.logout');
});

test('gets translations by locale with tag filter', function () {
    $webTag = Tag::factory()->create(['name' => 'web']);
    
    $translation1 = Translation::factory()->create(['locale' => 'en']);
    $translation1->tags()->attach($webTag);
    
    Translation::factory()->create(['locale' => 'en']); // No tags

    $results = $this->repository->getByLocale('en', ['web']);

    expect($results)->toHaveCount(1);
});

test('gets all tags ordered by name', function () {
    Tag::factory()->create(['name' => 'web']);
    Tag::factory()->create(['name' => 'mobile']);
    Tag::factory()->create(['name' => 'desktop']);

    $tags = $this->repository->getAllTags();

    expect($tags)->toHaveCount(3)
        ->and($tags->first()->name)->toBe('desktop')
        ->and($tags->last()->name)->toBe('web');
});

test('reuses existing tags when creating translation', function () {
    Tag::factory()->create(['name' => 'web']);
    
    $data = [
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ];

    $this->repository->create($data, ['web', 'mobile']);

    expect(Tag::count())->toBe(2); // Only one new tag created
});
