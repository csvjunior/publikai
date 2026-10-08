<?php

use App\Http\Controllers\AffiliateLinkController;
use App\Http\Controllers\AiAudioController;
use App\Http\Controllers\AiImageController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\AiVideoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\AvatarReferenceController;
use App\Http\Controllers\ContentBlueprintController;
use App\Http\Controllers\ContentScriptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdentityProposalController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReferenceAnalysisController;
use App\Http\Controllers\ReferenceContentController;
use App\Http\Controllers\ReferenceProfileController;
use App\Http\Controllers\ScriptAudioController;
use App\Http\Controllers\ScriptCompositionController;
use App\Http\Controllers\ScriptImageController;
use App\Http\Controllers\ScriptVideoController;
use App\Http\Controllers\SocialAccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/settings/ai', [AiSettingsController::class, 'index'])->name('settings.ai');
    Route::post('/settings/ai/test', [AiSettingsController::class, 'test'])
        ->middleware('throttle:5,1')
        ->name('settings.ai.test');
    Route::get('/settings/ai/images', [AiImageController::class, 'index'])->name('settings.ai.images');
    Route::post('/settings/ai/images', [AiImageController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('settings.ai.images.store');
    Route::get('/settings/ai/videos', [AiVideoController::class, 'index'])->name('settings.ai.videos');
    Route::post('/settings/ai/videos', [AiVideoController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('settings.ai.videos.store');
    Route::get('/settings/ai/audio', [AiAudioController::class, 'index'])->name('settings.ai.audio');
    Route::post('/settings/ai/audio', [AiAudioController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('settings.ai.audio.store');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->name('products.update');

    Route::get('/social-accounts', [SocialAccountController::class, 'index'])->name('social-accounts.index');
    Route::get('/social-accounts/create', [SocialAccountController::class, 'create'])->name('social-accounts.create');
    Route::post('/social-accounts', [SocialAccountController::class, 'store'])->name('social-accounts.store');
    Route::get('/social-accounts/{socialAccount}', [SocialAccountController::class, 'show'])->name('social-accounts.show');
    Route::get('/social-accounts/{socialAccount}/edit', [SocialAccountController::class, 'edit'])->name('social-accounts.edit');
    Route::match(['put', 'patch'], '/social-accounts/{socialAccount}', [SocialAccountController::class, 'update'])->name('social-accounts.update');

    Route::get('/personas', [PersonaController::class, 'index'])->name('personas.index');
    Route::get('/personas/create', [PersonaController::class, 'create'])->name('personas.create');
    Route::post('/personas', [PersonaController::class, 'store'])->name('personas.store');
    Route::get('/personas/{persona}', [PersonaController::class, 'show'])->name('personas.show');
    Route::get('/personas/{persona}/edit', [PersonaController::class, 'edit'])->name('personas.edit');
    Route::match(['put', 'patch'], '/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');

    Route::get('/avatars', [AvatarController::class, 'index'])->name('avatars.index');
    Route::get('/avatars/create', [AvatarController::class, 'create'])->name('avatars.create');
    Route::post('/avatars', [AvatarController::class, 'store'])->name('avatars.store');
    Route::get('/avatars/{avatar}', [AvatarController::class, 'show'])->name('avatars.show');
    Route::get('/avatars/{avatar}/edit', [AvatarController::class, 'edit'])->name('avatars.edit');
    Route::match(['put', 'patch'], '/avatars/{avatar}', [AvatarController::class, 'update'])->name('avatars.update');

    Route::get('/avatars/{avatar}/reference', [AvatarReferenceController::class, 'create'])->name('avatars.reference.create');
    Route::post('/avatars/{avatar}/reference', [AvatarReferenceController::class, 'store'])->name('avatars.reference.store');
    Route::delete('/avatars/{avatar}/references/{mediaAsset}', [AvatarReferenceController::class, 'destroy'])->name('avatars.references.destroy');
    Route::post('/avatars/{avatar}/references/{mediaAsset}/primary', [AvatarReferenceController::class, 'markPrimary'])->name('avatars.references.primary');

    Route::get('/blueprints', [ContentBlueprintController::class, 'index'])->name('blueprints.index');
    Route::get('/blueprints/create', [ContentBlueprintController::class, 'create'])->name('blueprints.create');
    Route::post('/blueprints', [ContentBlueprintController::class, 'store'])->name('blueprints.store');
    Route::get('/blueprints/{contentBlueprint}', [ContentBlueprintController::class, 'show'])->name('blueprints.show');
    Route::get('/blueprints/{contentBlueprint}/edit', [ContentBlueprintController::class, 'edit'])->name('blueprints.edit');
    Route::match(['put', 'patch'], '/blueprints/{contentBlueprint}', [ContentBlueprintController::class, 'update'])->name('blueprints.update');

    Route::get('/scripts', [ContentScriptController::class, 'index'])->name('scripts.index');
    Route::get('/scripts/create', [ContentScriptController::class, 'create'])->name('scripts.create');
    Route::post('/scripts', [ContentScriptController::class, 'store'])->name('scripts.store');
    Route::post('/scripts/generate', [ContentScriptController::class, 'generate'])
        ->middleware('throttle:5,1')
        ->name('scripts.generate');
    Route::get('/scripts/{contentScript}', [ContentScriptController::class, 'show'])->name('scripts.show');
    Route::get('/scripts/{contentScript}/edit', [ContentScriptController::class, 'edit'])->name('scripts.edit');
    Route::match(['put', 'patch'], '/scripts/{contentScript}', [ContentScriptController::class, 'update'])->name('scripts.update');
    Route::post('/scripts/{contentScript}/ready', [ContentScriptController::class, 'ready'])->name('scripts.ready');
    Route::post('/scripts/{contentScript}/approve', [ContentScriptController::class, 'approve'])->name('scripts.approve');

    Route::scopeBindings()->group(function () {
        Route::get('/scripts/{contentScript}/images/create', [ScriptImageController::class, 'create'])->name('scripts.images.create');
        Route::post('/scripts/{contentScript}/images', [ScriptImageController::class, 'store'])->name('scripts.images.store');
        Route::post('/scripts/{contentScript}/images/{mediaAsset}/primary', [ScriptImageController::class, 'markPrimary'])->name('scripts.images.primary');
    });

    Route::scopeBindings()->group(function () {
        Route::get('/scripts/{contentScript}/images/create', [ScriptImageController::class, 'create'])->name('scripts.images.create');
        Route::post('/scripts/{contentScript}/images', [ScriptImageController::class, 'store'])->name('scripts.images.store');
        Route::post('/scripts/{contentScript}/images/{mediaAsset}/primary', [ScriptImageController::class, 'markPrimary'])->name('scripts.images.primary');
        Route::get('/scripts/{contentScript}/images/{mediaAsset}/edit', [ScriptImageController::class, 'createVariation'])->name('scripts.images.edit');
        Route::post('/scripts/{contentScript}/images/{mediaAsset}/edit', [ScriptImageController::class, 'storeVariation'])->name('scripts.images.edit.store');
        Route::get('/scripts/{contentScript}/videos/create', [ScriptVideoController::class, 'create'])->name('scripts.videos.create');
        Route::post('/scripts/{contentScript}/videos', [ScriptVideoController::class, 'store'])->name('scripts.videos.store');
        Route::get('/scripts/{contentScript}/compositions/create', [ScriptCompositionController::class, 'create'])->name('scripts.compositions.create');
        Route::post('/scripts/{contentScript}/compositions', [ScriptCompositionController::class, 'store'])->name('scripts.compositions.store');
        Route::get('/scripts/{contentScript}/audio/create', [ScriptAudioController::class, 'create'])->name('scripts.audio.create');
        Route::post('/scripts/{contentScript}/audio', [ScriptAudioController::class, 'store'])->name('scripts.audio.store');
    });

    Route::get('/references', [ReferenceProfileController::class, 'index'])->name('references.index');
    Route::get('/references/create', [ReferenceProfileController::class, 'create'])->name('references.create');
    Route::post('/references', [ReferenceProfileController::class, 'store'])->name('references.store');
    Route::get('/references/{referenceProfile}', [ReferenceProfileController::class, 'show'])->name('references.show');
    Route::get('/references/{referenceProfile}/edit', [ReferenceProfileController::class, 'edit'])->name('references.edit');
    Route::match(['put', 'patch'], '/references/{referenceProfile}', [ReferenceProfileController::class, 'update'])->name('references.update');

    Route::scopeBindings()->group(function () {
        Route::post('/references/{referenceProfile}/contents', [ReferenceContentController::class, 'store'])->name('reference-contents.store');
        Route::match(['put', 'patch'], '/references/{referenceProfile}/contents/{referenceContent}', [ReferenceContentController::class, 'update'])->name('reference-contents.update');
    });

    Route::post('/references/{referenceProfile}/analyses', [ReferenceAnalysisController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('references.analyses.store');

    Route::scopeBindings()->group(function () {
        Route::post('/references/{referenceProfile}/proposals', [IdentityProposalController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('references.proposals.store');
        Route::match(['put', 'patch'], '/references/{referenceProfile}/proposals/{identityProposal}', [IdentityProposalController::class, 'update'])->name('references.proposals.update');
        Route::post('/references/{referenceProfile}/proposals/{identityProposal}/apply', [IdentityProposalController::class, 'apply'])->name('references.proposals.apply');
        Route::post('/references/{referenceProfile}/proposals/{identityProposal}/discard', [IdentityProposalController::class, 'discard'])->name('references.proposals.discard');
    });
    Route::scopeBindings()->group(function () {
        Route::post('/products/{product}/affiliate-links', [AffiliateLinkController::class, 'store'])->name('affiliate-links.store');
        Route::match(['put', 'patch'], '/products/{product}/affiliate-links/{affiliateLink}', [AffiliateLinkController::class, 'update'])->name('affiliate-links.update');
        Route::delete('/products/{product}/affiliate-links/{affiliateLink}', [AffiliateLinkController::class, 'destroy'])->name('affiliate-links.destroy');
    });
});
