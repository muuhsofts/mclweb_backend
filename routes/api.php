<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FtGroupController;
use App\Http\Controllers\LeadershipController;
use App\Http\Controllers\DiversityInclusionController;
use App\Http\Controllers\SustainabilityController;
use App\Http\Controllers\GivingBackController;
use App\Http\Controllers\FtPink130Controller;
use App\Http\Controllers\OurStandardController;
use App\Http\Controllers\SubStandardController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\FtHomeController;
use App\Http\Controllers\LeadershipHomeController;
use App\Http\Controllers\MclPink130Controller;
use App\Http\Controllers\MclHomeController;
use App\Http\Controllers\MclGroupController;
use App\Http\Controllers\DiversityHomeController;
use App\Http\Controllers\SustainabilityHomeController;
use App\Http\Controllers\GivingBackHomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Pink130HomeController;
use App\Http\Controllers\Pink130Controller;
use App\Http\Controllers\OurStandardHomeController;
use  App\Http\Controllers\ServicesHomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\NewsHomeController;
use App\Http\Controllers\API\SubNewsController;
use App\Http\Controllers\ContactHomeController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\API\ContactInfoController;
use App\Http\Controllers\WhatWeDoHomeController;
use App\Http\Controllers\WhatWeDoController;
use App\Http\Controllers\BlogHomeController;
use App\Http\Controllers\API\SubcategoryWeDoController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\SubBlogController;
use App\Http\Controllers\BenefitiesHomeController;
use App\Http\Controllers\BenefitsController;
use App\Http\Controllers\ValuesHomeController;
use App\Http\Controllers\ValuesController;
use App\Http\Controllers\StayConnectedHomeController;
use App\Http\Controllers\StayConnectedController;
use App\Http\Controllers\EarycareHomeController;
use App\Http\Controllers\EarlyCareersController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AboutMwananchiController;
use App\Http\Controllers\SubEventController;
use App\Http\Controllers\SubscriptionController;


// Public Routes
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized user! Please login to access the API'], 401);
})->name('login');

// Authentication Routes
Route::post('/auth/add-user', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
// Route::get('/auth/google/redirect', [AnuthController::class, 'redirectToGoogle']);
// Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/auth/request-reset', [PasswordResetController::class, 'requestPasswordReset']);
Route::post('/auth/password-reset', [PasswordResetController::class, 'resetPassword']);
Route::get('/slider-imgs', [AboutController::class, 'AboutSliders']); 
 Route::get('/homeSliders', [CompanyController::class, 'homeSliders']);
Route::get('/leadershipHomeSlider', [LeadershipHomeController::class, 'leadershipHomeSlider']);
 Route::get('/sliders', [MclHomeController::class, 'mclhmeSlider'])->name('mcl-home.sliders'); // Possibly a frontend slider endpoint
 Route::get('/latest', [MclGroupController::class, 'latest'])->name('mcl-groups.latest');
 Route::get('/d-and-inc/homeSlider', [DiversityHomeController::class, 'homeSlider']);
  Route::get('/sust/homeSlider', [SustainabilityHomeController::class, 'sustainabilityHomeSlider']);
   Route::get('/giving-back/slider', [GivingBackHomeController::class, 'givingBackHomeSlider']);
   Route::get('/pink130Sliders', [Pink130HomeController::class, 'pink130Sliders']);
   Route::get('/ourStandardHomeSlider', [OurStandardHomeController::class, 'ourStandardHomeSlider']);
     Route::get('/servicesHomeSlider', [ServicesHomeController::class, 'servicesHomeSlider']);
     Route::get('/news-home-slider', [NewsHomeController::class, 'newsHomeSlider']);
     Route::get('/contactHomeSlider', [ContactHomeController::class, 'contactHomeSlider']);
     Route::get('/latest/mcl-groups', [MclGroupController::class, 'latest'])->name('mcl-groups.latest');
     Route::get('/latest/service', [ServiceController::class, 'latestservice']);
       Route::get('/latestnew', [NewsController::class, 'latestnew']);
       Route::get('/latestleadership', [LeadershipController::class, 'latestleadership']);
       Route::get('/latestdiversityinclusion', [DiversityInclusionController::class, 'latestdiversityinclusion']);
       Route::get('/latestSustainability', [SustainabilityController::class, 'latestSustainability']);
       Route::get('/latestGivingBack', [GivingBackController::class, 'latestGivingBack']);
       Route::get('/latestMclPink130', [Pink130Controller::class, 'latestMclPink130']);
       Route::get('/latestOurStandard', [OurStandardController::class, 'latestOurStandard']);
        Route::get('/allMclGroups', [MclGroupController::class, 'allMclgroup'])->name('allMclGroups');
        Route::get('/allLeadership', [LeadershipController::class, 'allLeadership']);
        Route::get('/allDiversitiesAndIclusion', [DiversityInclusionController::class, 'allDiversitiesAndIclusion']); 
        Route::get('/allSustainability', [SustainabilityController::class, 'allSustainability']);
        Route::get('/allGivingBack', [GivingBackController::class, 'allGivingBack']);
         Route::get('/allMCLpink', [Pink130Controller::class, 'allMCLpink']);
         Route::get('/allOurStandards', [OurStandardController::class, 'allOurStandards']);
        Route::get('/allService', [ServiceController::class, 'allService']);
         Route::get('/allNews', [NewsController::class, 'allNews']);
         Route::get('/subNews', [SubNewsController::class, 'subNews']);
          Route::get('/allContactUs', [ContactUsController::class, 'allContactUs']);
          Route::get('/contactInfo', [ContactInfoController::class, 'contactInfo']);
          Route::get('/what-we-do-homes/slider', [WhatWeDoHomeController::class, 'whatWeDoHomeSlider']);
           Route::get('/we-do/all', [WhatWeDoController::class, 'allRecords']);
          Route::get('/blog-home-sliders/public', [BlogHomeController::class, 'blogHomeSlider']);
          Route::get('/blogs/all', [BlogController::class, 'allBlogs']);
          Route::get('/sub-blogs/all', [SubBlogController::class, 'allSubBlogs']);
          Route::get('/benefities-home-slider', [BenefitiesHomeController::class, 'benefitiesHomeSlider']);
          Route::get('/benefits/all', [BenefitsController::class, 'allBenefits']);
          Route::get('/values-home/slider', [ValuesHomeController::class, 'valuesHomeSlider']);
          Route::get('/values/all', [ValuesController::class, 'allValues']);
       Route::get('/earlycareer/sliders', [EarycareHomeController::class, 'earycareHomeSlider'])->name('earycare-home.sliders');
       Route::get('/early-careers/all', [EarlyCareersController::class, 'allEarlyCareers']);
        Route::get('/stayconnected/sliders', [StayConnectedHomeController::class, 'stayConnectedHomeSlider'])->name('stay-connected-home.sliders');
      Route::get('/stay-connected/all', [StayConnectedController::class, 'allStayConnected']);
      Route::get('/latestEarlyCareer', [EarlyCareersController::class, 'latestEarlyCareer']);
      Route::get('/allBrands', [BrandController::class, 'allBrands']);
      Route::get('/latestService', [ServicesHomeController::class, 'latestService']);
        Route::get('/all-events', [EventController::class, 'allEvents']);
        Route::get('/latestEvent', [EventController::class, 'latestEvent']);
     Route::get('/about-mwananchi/all', [AboutMwananchiController::class, 'allRecords']);
       Route::get('/all/sub-events', [SubEventController::class, 'allEvents']);
       Route::get('/allsubscriptions', [SubscriptionController::class, 'allsubscriptions']);
        Route::get('/latestbrand', [BrandController::class, 'latestbrand']);
        Route::get('/readmore-news/{news_id}', [NewsController::class, 'newsByid']);
       

       
// Protected Routes
Route::middleware(['auth:sanctum', 'token.expiration'])->group(function () {
    // User Routes
    Route::get('/all/users', [AuthController::class, 'users']);
    Route::get('/users/byname', [AuthController::class, 'dropdownUsersByName']);
    Route::get('/users/byrole', [AuthController::class, 'dropdownUsersByRole']);
    Route::get('/user/profile', [AuthController::class, 'getLoggedUserProfile']);
    Route::get('/user/withrole', [AuthController::class, 'getUsersWithRoles']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('/count/users', [AuthController::class, 'countUsers']);
    Route::get('/logged-user/name', [AuthController::class, 'getLoggedUserName']);
    Route::get('/user-dropdown', [AuthController::class, 'getUsersForDropdown']);
    Route::get('/user/{user_id}', [AuthController::class, 'showUserById']);
    Route::put('/update-user/{user_id}', [AuthController::class, 'updateUser']);
    Route::delete('/auth/user/{user_id}', [AuthController::class, 'deleteUser']);
    Route::get('/audit-trail', [AuthController::class, 'getAuditTrail']);
    Route::post('/store-cookies', [AuthController::class, 'storeCookies']);

    // Role Routes
    Route::apiResource('/auth/roles', RoleController::class);
    Route::get('/count/roles', [RoleController::class, 'countRoles']);
    Route::get('/roles/dropdown-options', [RoleController::class, 'getDropdownOptions']);

    // Company Routes
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company_id}', [CompanyController::class, 'show']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::post('/companies/{company_id}', [CompanyController::class, 'update']); 
    Route::delete('/companies/{company_id}', [CompanyController::class, 'destroy']);


    


Route::resource('diversity-inclusion', DiversityInclusionController::class);
Route::post('/diversity-inclusion/{diversity_id}/update', [DiversityInclusionController::class, 'update']);


Route::get('/sustainability', [SustainabilityController::class, 'index']);
Route::post('/sustainability', [SustainabilityController::class, 'store'])->middleware('auth:sanctum');
Route::get('/sustainability/{sustain_id}', [SustainabilityController::class, 'show']);
Route::post('/sustainability/{sustain_id}/update', [SustainabilityController::class, 'update']);
Route::put('/sustainability/{sustain_id}', [SustainabilityController::class, 'update']);
Route::delete('/sustainability/{sustain_id}', [SustainabilityController::class, 'destroy']);



Route::prefix('sustainability-homes')->group(function () {
    Route::get('/', [SustainabilityHomeController::class, 'index']);
    Route::get('/{sustainability_home_id}', [SustainabilityHomeController::class, 'show']);
    Route::post('/', [SustainabilityHomeController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/{sustainability_home_id}', [SustainabilityHomeController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{sustainability_home_id}', [SustainabilityHomeController::class, 'destroy'])->middleware('auth:sanctum');
});




Route::get('/giving-backs', [GivingBackController::class, 'index']);
Route::post('/giving-backs', [GivingBackController::class, 'store']);
Route::get('/giving-backs/{giving_id}', [GivingBackController::class, 'show']);
Route::post('/giving-backs/{giving_id}/update', [GivingBackController::class, 'update']);
Route::delete('/giving-backs/{giving_id}', [GivingBackController::class, 'destroy']);


Route::prefix('giving-back-homes')->group(function () {
    Route::get('/', [GivingBackHomeController::class, 'index']);
    Route::get('/{giving_back_id}', [GivingBackHomeController::class, 'show']);
    Route::post('/', [GivingBackHomeController::class, 'store']);
    Route::put('/{giving_back_id}', [GivingBackHomeController::class, 'update']);
    Route::delete('/{giving_back_id}', [GivingBackHomeController::class, 'destroy']);
   
});

Route::get('/mcl-pink-130', [MclPink130Controller::class, 'index']);

Route::post('/mcl-pink-130', [MclPink130Controller::class, 'store']);
Route::get('/mcl-pink-130/{mcl_id}', [MclPink130Controller::class, 'show']);
Route::post('/mcl-pink-130/{mcl_id}/update', [MclPink130Controller::class, 'update']);
Route::delete('/mcl-pink-130/{mcl_id}', [MclPink130Controller::class, 'destroy']);



Route::get('/our-standard', [OurStandardController::class, 'index']);
Route::post('/our-standard', [OurStandardController::class, 'store'])->middleware('auth:sanctum');
Route::get('/our-standard/{our_id}', [OurStandardController::class, 'show']);
Route::post('/our-standard/{our_id}/update', [OurStandardController::class, 'update']);
Route::delete('/our-standard/{our_id}', [OurStandardController::class, 'destroy']);


Route::prefix('our-standard-home')->group(function () {
    Route::get('/', [OurStandardHomeController::class, 'index']);
    Route::get('/latest', [OurStandardHomeController::class, 'latest']);
    Route::post('/', [OurStandardHomeController::class, 'store']);
    Route::get('/{id}', [OurStandardHomeController::class, 'show']);
    Route::post('/{id}/update', [OurStandardHomeController::class, 'update']);
    Route::delete('/{id}', [OurStandardHomeController::class, 'destroy']);
});

Route::get('/sub-standard', [SubStandardController::class, 'index']);
Route::get('/sub-standard/latest', [SubStandardController::class, 'latest']);
Route::post('/sub-standard', [SubStandardController::class, 'store']);
Route::get('/sub-standard/{subStandard_id}', [SubStandardController::class, 'show']);
Route::post('/sub-standard/{subStandard_id}/update', [SubStandardController::class, 'update']);
// This route now EXACTLY matches the URL and method from your React app.
Route::post('/our-standard/{our_id}', [OurStandardController::class, 'update']);
Route::delete('/sub-standard/{subStandard_id}', [SubStandardController::class, 'destroy']);


Route::prefix('about')->group(function () {
    Route::get('/', [AboutController::class, 'index']); // Get all about entries
    Route::post('/', [AboutController::class, 'store']); // Create about entry
    Route::get('/{about_id}', [AboutController::class, 'show']); // Get single about entry
    Route::post('/{about_id}/update', [AboutController::class, 'update']); // Update about entry
    Route::delete('/{about_id}', [AboutController::class, 'destroy']); // Delete about entry
    Route::get('/count', [AboutController::class, 'countAbout']); // Count about entries
    Route::get('/dropdown', [AboutController::class, 'getDropdownOptions']); // Dropdown options
});

  Route::get('/about-mwananchi', [AboutMwananchiController::class, 'index']);
  Route::get('/about-mwananchi/count', [AboutMwananchiController::class, 'countRecords']);
  Route::get('/about-mwananchi/latest', [AboutMwananchiController::class, 'latestRecord']);
  Route::get('/about-mwananchi/{id}', [AboutMwananchiController::class, 'show']);
  Route::post('/about-mwananchi', [AboutMwananchiController::class, 'store']);
  Route::post('/about-mwananchi/{id}', [AboutMwananchiController::class, 'update']);
  Route::delete('/about-mwananchi/{id}', [AboutMwananchiController::class, 'destroy']);


Route::get('/leadership-homes', [LeadershipHomeController::class, 'index']);
Route::get('/leadership-homes/slider', [LeadershipHomeController::class, 'leadershipHomeSlider']);
Route::get('/leadership-homes/{leadership_home_id}', [LeadershipHomeController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/leadership-homes', [LeadershipHomeController::class, 'store']);
    Route::put('/leadership-homes/{leadership_home_id}', [LeadershipHomeController::class, 'update']);
    Route::delete('/leadership-homes/{leadership_home_id}', [LeadershipHomeController::class, 'destroy']);
});




    // Leadership Routes
Route::get('/leadership', [LeadershipController::class, 'index']);
Route::get('/leadership/{leadership_id}', [LeadershipController::class, 'show']);
Route::post('/leadership', [LeadershipController::class, 'store']);
Route::post('/leadership/{leadership_id}/update', [LeadershipController::class, 'update']);
Route::delete('/leadership/{leadership_id}', [LeadershipController::class, 'destroy']);
Route::get('/count/leadership', [LeadershipController::class, 'countLeadership'])->name('leadership.count');
    

Route::prefix('mcl-home')->group(function () {
    Route::get('/', [MclHomeController::class, 'index'])->name('mcl-home.index'); // List all sliders
    Route::get('/sliders', [MclHomeController::class, 'mclhmeSlider'])->name('mcl-home.sliders'); // Possibly a frontend slider endpoint
    Route::get('/{mcl_home_id}', [MclHomeController::class, 'show'])->name('mcl-home.show'); // Fetch a single slider

    Route::post('/', [MclHomeController::class, 'store'])->name('mcl-home.store'); // Create a slider
    Route::post('/{mcl_home_id}', [MclHomeController::class, 'update'])->name('mcl-home.update'); // Update a slider
    Route::delete('/{mcl_home_id}', [MclHomeController::class, 'destroy'])->name('mcl-home.destroy'); // Delete a slider
});

Route::prefix('mcl-groups')->group(function () {
    Route::get('/', [MclGroupController::class, 'index'])->name('mcl-groups.index');
   
    Route::get('/all', [MclGroupController::class, 'allMclgroup'])->name('mcl-groups.all');
    Route::get('/{mcl_id}', [MclGroupController::class, 'show'])->name('mcl-groups.show');
    
 Route::post('/', [MclGroupController::class, 'store'])->name('mcl-groups.store');
        Route::post('/{mcl_id}', [MclGroupController::class, 'update'])->name('mcl-groups.update');
        Route::delete('/{mcl_id}', [MclGroupController::class, 'destroy'])->name('mcl-groups.destroy');
});

Route::get('/count/mcl-groups', [MclGroupController::class, 'countMclGroups'])->name('mcl-groups.count');

Route::prefix('diversity-home')->group(function () {
    Route::get('/', [DiversityHomeController::class, 'index']); // Get all diversity_home entries
    Route::post('/', [DiversityHomeController::class, 'store']); // Create diversity_home entry
    Route::get('/{dhome_id}', [DiversityHomeController::class, 'show']); // Get single diversity_home entry
    Route::post('/{dhome_id}/update', [DiversityHomeController::class, 'update']); // Update diversity_home entry
    Route::delete('/{dhome_id}', [DiversityHomeController::class, 'destroy']); // Delete diversity_home entry
    Route::get('/count', [DiversityHomeController::class, 'countDiversityHome']); // Count diversity_home entries
    Route::get('/dropdown', [DiversityHomeController::class, 'getDropdownOptions']); // Dropdown options
});




// API routes for Pink130 resource
Route::prefix('pink-130')->group(function () {
    Route::get('/', [Pink130Controller::class, 'index'])->name('pink130.index');

    Route::get('/{pink_id}', [Pink130Controller::class, 'show'])->name('pink130.show');
    
    // Protected routes requiring authentication
    Route::post('/', [Pink130Controller::class, 'store'])->name('pink130.store');
    Route::post('/{pink_id}/update', [Pink130Controller::class, 'update'])->name('pink130.update');
    Route::delete('/{pink_id}', [Pink130Controller::class, 'destroy'])->name('pink130.destroy');
});



Route::prefix('mcl-pink-130-home')->group(function () {
    Route::get('/', [Pink130HomeController::class, 'index']);
    Route::get('/latest', [Pink130HomeController::class, 'latest']);
    Route::post('/', [Pink130HomeController::class, 'store']);
    Route::get('/{ft_pink_id}', [Pink130HomeController::class, 'show']);
    Route::post('/{ft_pink_id}/update', [Pink130HomeController::class, 'update']);
    Route::delete('/{ft_pink_id}', [Pink130HomeController::class, 'destroy']);
});



Route::prefix('services-homes')->group(function () {
    Route::get('/', [ServicesHomeController::class, 'index']);
    Route::get('/{services_home_id}', [ServicesHomeController::class, 'show']);
    Route::post('/', [ServicesHomeController::class, 'store']);
    Route::put('/{services_home_id}', [ServicesHomeController::class, 'update']);
    Route::delete('/{services_home_id}', [ServicesHomeController::class, 'destroy']);
  
});


Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service_id}', [ServiceController::class, 'show']);
Route::post('/services', [ServiceController::class, 'store']);
Route::post('/services/{service_id}/update', [ServiceController::class, 'update']);
Route::delete('/services/{service_id}', [ServiceController::class, 'destroy']);
Route::get('/count/services', [ServiceController::class, 'countServices'])->name('services.count');


Route::get('/news-homes', [NewsHomeController::class, 'index']);
Route::get('/news-homes/{news_home_id}', [NewsHomeController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/news-homes', [NewsHomeController::class, 'store']);
    Route::post('/news-homes/{news_home_id}', [NewsHomeController::class, 'update']); // Changed from PUT to POST
    Route::delete('/news-homes/{news_home_id}', [NewsHomeController::class, 'destroy']);
});

Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
  
    Route::get('/{news_id}', [NewsController::class, 'show']);
    Route::post('/', [NewsController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/{news_id}/update', [NewsController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{news_id}', [NewsController::class, 'destroy'])->middleware('auth:sanctum');
});
   Route::get('/count/news', [NewsController::class, 'countNews'])->name('news.count');
   
// Existing Routes (Modified for Route Model Binding)
Route::get('/sub-news', [SubNewsController::class, 'index']);
Route::post('/sub-news', [SubNewsController::class, 'store']);
// The parameter name must match the variable name in the controller method ($subNews)
Route::get('/sub-news/{subNews}', [SubNewsController::class, 'show']);
Route::post('/sub-news/{subNews}/update', [SubNewsController::class, 'update']);
Route::delete('/sub-news/{subNews}', [SubNewsController::class, 'destroy']);



Route::get('/contact-homes', [ContactHomeController::class, 'index']);
Route::post('/contact-homes', [ContactHomeController::class, 'store']);
Route::get('/contact-homes/{contactHome}', [ContactHomeController::class, 'show']);
Route::post('/contact-homes/{contactHome}/update', [ContactHomeController::class, 'update']);
Route::delete('/contact-homes/{contactHome}', [ContactHomeController::class, 'destroy']);



    Route::get('/contact-us', [ContactUsController::class, 'index']);
    Route::get('/contact-us/{contactus_id}', [ContactUsController::class, 'show']);
    Route::post('/contact-us', [ContactUsController::class, 'store']);
    Route::post('/contact-us/{contactus_id}', [ContactUsController::class, 'update']);
    Route::delete('/contact-us/{contactus_id}', [ContactUsController::class, 'destroy']);

    Route::prefix('diversity')->group(function () {
    // Public routes
    Route::get('/', [DiversityInclusionController::class, 'index']); // GET /api/diversity
    Route::get('/latest', [DiversityInclusionController::class, 'latestdiversityinclusion']); // GET /api/diversity/latest
    Route::get('/{diversity_id}', [DiversityInclusionController::class, 'show']); // GET /api/diversity/{diversity_id}

    // Protected routes (require Sanctum auth)
    Route::post('/', [DiversityInclusionController::class, 'store']); // POST /api/diversity
    Route::post('/{diversity_id}', [DiversityInclusionController::class, 'update']); // POST /api/diversity/{diversity_id}
    Route::delete('/{diversity_id}', [DiversityInclusionController::class, 'destroy']); // DELETE /api/diversity/{diversity_id}
});


Route::apiResource('contact-info', ContactInfoController::class);
Route::get('/contact-us-dropdown', [ContactUsController::class, 'contactDropDown']);



Route::get('/what-we-do-homes', [WhatWeDoHomeController::class, 'index']);
Route::get('/what-we-do-homes/{what_we_do_id}', [WhatWeDoHomeController::class, 'show']);
Route::post('/what-we-do-homes', [WhatWeDoHomeController::class, 'store']);
Route::post('/what-we-do-homes/{what_we_do_id}', [WhatWeDoHomeController::class, 'update']);
Route::delete('/what-we-do-homes/{what_we_do_id}', [WhatWeDoHomeController::class, 'destroy']);


Route::get('/we-do', [WhatWeDoController::class, 'index']);
Route::get('/we-do/{what_we_do_id}', [WhatWeDoController::class, 'show']);
Route::post('/we-do', [WhatWeDoController::class, 'store']);
Route::post('/we-do/{what_we_do_id}', [WhatWeDoController::class, 'update']);
Route::delete('/we-do/{what_we_do_id}', [WhatWeDoController::class, 'destroy']);
Route::get('/whatwedo/categories', [WhatWeDoController::class, 'fetchAllCategories']);

Route::prefix('subcategories')->group(function () {
    Route::get('/', [SubcategoryWeDoController::class, 'index']);
    Route::get('/{subcategoryWeDo}', [SubcategoryWeDoController::class, 'show']);
    Route::post('/', [SubcategoryWeDoController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/{subcategoryWeDo}', [SubcategoryWeDoController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{subcategoryWeDo}', [SubcategoryWeDoController::class, 'destroy'])->middleware('auth:sanctum');
});
    




Route::get('/blog-home-sliders', [BlogHomeController::class, 'index']);
Route::get('/blog-home-sliders/{blog_home_id}', [BlogHomeController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blog-home-sliders', [BlogHomeController::class, 'store']);
    Route::put('/blog-home-sliders/{blog_home_id}', [BlogHomeController::class, 'update']);
    Route::delete('/blog-home-sliders/{blog_home_id}', [BlogHomeController::class, 'destroy']);
});




Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/latest', [BlogController::class, 'latestBlog']);
Route::get('/blogs/{blog_id}', [BlogController::class, 'show']);
Route::post('/blogs', [BlogController::class, 'store']);
Route::put('/blogs/{blog_id}', [BlogController::class, 'update']);
Route::delete('/blogs/{blog_id}', [BlogController::class, 'destroy']);
  Route::get('/blogs-dropdown', [BlogController::class, 'blogsDropDown']);

Route::get('/sub-blogs', [SubBlogController::class, 'index']);
Route::get('/sub-blogs/latest', [SubBlogController::class, 'latestSubBlog']);
Route::get('/sub-blogs/{sublog_id}', [SubBlogController::class, 'show']);
 Route::post('/sub-blogs', [SubBlogController::class, 'store']);
    Route::post('/sub-blogs/{sublog_id}', [SubBlogController::class, 'update']);
    Route::delete('/sub-blogs/{sublog_id}', [SubBlogController::class, 'destroy']);



Route::get('/benefities-home', [BenefitiesHomeController::class, 'index']);
Route::get('/benefities-home/{benefit_home_id}', [BenefitiesHomeController::class, 'show']);
Route::post('/benefities-home', [BenefitiesHomeController::class, 'store']);
Route::put('/benefities-home/{benefit_home_id}', [BenefitiesHomeController::class, 'update']);
Route::delete('/benefities-home/{benefit_home_id}', [BenefitiesHomeController::class, 'destroy']);


Route::get('/benefits', [BenefitsController::class, 'index']);
Route::get('/benefits/{benefit_id}', [BenefitsController::class, 'show']);
Route::post('/benefits', [BenefitsController::class, 'store']);
Route::post('/benefits/{benefit_id}', [BenefitsController::class, 'update']);
Route::delete('/benefits/{benefit_id}', [BenefitsController::class, 'destroy']);


Route::get('/values-home', [ValuesHomeController::class, 'index']);

Route::get('/values-home/{values_home_id}', [ValuesHomeController::class, 'show']);
Route::post('/values-home', [ValuesHomeController::class, 'store']);
Route::put('/values-home/{values_home_id}', [ValuesHomeController::class, 'update']);
Route::delete('/values-home/{values_home_id}', [ValuesHomeController::class, 'destroy']);



Route::get('/values', [ValuesController::class, 'index']);
Route::get('/values/{value_id}', [ValuesController::class, 'show']);
Route::post('/values', [ValuesController::class, 'store']);
Route::post('/values/{value_id}', [ValuesController::class, 'update']);
Route::delete('/values/{value_id}', [ValuesController::class, 'destroy']);


Route::prefix('stay-connected-home')->group(function () {
    Route::get('/', [StayConnectedHomeController::class, 'index'])->name('stay-connected-home.index');
    Route::get('/{stay_connected_id}', [StayConnectedHomeController::class, 'show'])->name('stay-connected-home.show');
    Route::post('/', [StayConnectedHomeController::class, 'store'])->name('stay-connected-home.store');
    Route::put('/{stay_connected_id}', [StayConnectedHomeController::class, 'update'])->name('stay-connected-home.update');
    Route::delete('/{stay_connected_id}', [StayConnectedHomeController::class, 'destroy'])->name('stay-connected-home.destroy');
});




Route::get('/stay-connected', [StayConnectedController::class, 'index']);

Route::get('/stay-connected/{stay_connected_id}', [StayConnectedController::class, 'show']);
Route::post('/stay-connected', [StayConnectedController::class, 'store']);
Route::post('/stay-connected/{stay_connected_id}', [StayConnectedController::class, 'update']);
Route::delete('/stay-connected/{stay_connected_id}', [StayConnectedController::class, 'destroy']);


Route::prefix('earycare-home')->group(function () {
    Route::get('/', [EarycareHomeController::class, 'index'])->name('earycare-home.index');
    Route::get('/{earycare_id}', [EarycareHomeController::class, 'show'])->name('earycare-home.show');
    Route::post('/', [EarycareHomeController::class, 'store'])->name('earycare-home.store');
    Route::put('/{earycare_id}', [EarycareHomeController::class, 'update'])->name('earycare-home.update');
    Route::delete('/{earycare_id}', [EarycareHomeController::class, 'destroy'])->name('earycare-home.destroy');
});
 

 Route::get('/early-careers', [EarlyCareersController::class, 'index']);
Route::get('/early-careers/{early_career_id}', [EarlyCareersController::class, 'show']);
Route::post('/early-careers', [EarlyCareersController::class, 'store']);
Route::post('/early-careers/{early_career_id}', [EarlyCareersController::class, 'update']);
Route::delete('/early-careers/{early_career_id}', [EarlyCareersController::class, 'destroy']);


Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/latest', [EventController::class, 'latestEvent']);
    // New route for dropdown data
    Route::get('/dropdown-data', [EventController::class, 'getDropdownData']);
    Route::get('/count', [EventController::class, 'countEvents']);
    Route::get('/{event_id}', [EventController::class, 'show']);
    Route::post('/', [EventController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/{event_id}/update', [EventController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{event_id}', [EventController::class, 'destroy'])->middleware('auth:sanctum');
}); 


// Subscription API Routes
Route::apiResource('subscriptions', SubscriptionController::class);

// Since the frontend code you provided for 'edit' uses a POST to a custom '/update' URL,
// let's add that for compatibility. The apiResource 'update' is more standard (PUT/PATCH).
// You can use either, but this makes the provided React code work without changes.
Route::post('subscriptions/{subscription_id}/update', [SubscriptionController::class, 'update']);

Route::prefix('sub-events')->group(function () {
    Route::get('/', [SubEventController::class, 'index']);
  
    Route::get('/count', [SubEventController::class, 'countEvents']);
    Route::get('/latest', [SubEventController::class, 'latestEvent']);
    Route::get('/{event_id}', [SubEventController::class, 'show']);
  
        Route::post('/', [SubEventController::class, 'store']);
        Route::post('/{event_id}', [SubEventController::class, 'update']);
        Route::delete('/{event_id}', [SubEventController::class, 'destroy']);

});

//brands
Route::resource('brands', BrandController::class);
Route::post('/edit/brand/{brand_id}', [BrandController::class, 'update']);
});

