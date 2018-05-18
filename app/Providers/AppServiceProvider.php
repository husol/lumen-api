<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            \App\DataServices\User\UserRepoInterface::class,
            \App\DataServices\User\UserRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Device\DeviceRepoInterface::class,
            \App\DataServices\Device\DeviceRepo::class
        );
        $this->app->singleton(
            \App\DataServices\NotiMessage\NotiMessageRepoInterface::class,
            \App\DataServices\NotiMessage\NotiMessageRepo::class
        );
        $this->app->singleton(
            \App\DataServices\People\PeopleRepoInterface::class,
            \App\DataServices\People\PeopleRepo::class
        );
        $this->app->singleton(
            \App\DataServices\PeopleExperience\PeopleExperienceRepoInterface::class,
            \App\DataServices\PeopleExperience\PeopleExperienceRepo::class
        );
        $this->app->singleton(
            \App\DataServices\PeopleFeedback\PeopleFeedbackRepoInterface::class,
            \App\DataServices\PeopleFeedback\PeopleFeedbackRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Company\CompanyRepoInterface::class,
            \App\DataServices\Company\CompanyRepo::class
        );
        $this->app->singleton(
            \App\DataServices\CompanyProject\CompanyProjectRepoInterface::class,
            \App\DataServices\CompanyProject\CompanyProjectRepo::class
        );
        $this->app->singleton(
            \App\DataServices\CompanyFeedback\CompanyFeedbackRepoInterface::class,
            \App\DataServices\CompanyFeedback\CompanyFeedbackRepo::class
        );
        $this->app->singleton(
            \App\DataServices\PackageType\PackageTypeRepoInterface::class,
            \App\DataServices\PackageType\PackageTypeRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Package\PackageRepoInterface::class,
            \App\DataServices\Package\PackageRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Job\JobRepoInterface::class,
            \App\DataServices\Job\JobRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Order\OrderRepoInterface::class,
            \App\DataServices\Order\OrderRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Transaction\TransactionRepoInterface::class,
            \App\DataServices\Transaction\TransactionRepo::class
        );
        $this->app->singleton(
            \App\DataServices\PeopleJob\PeopleJobRepoInterface::class,
            \App\DataServices\PeopleJob\PeopleJobRepo::class
        );
        $this->app->singleton(
            \App\DataServices\PeopleCategory\PeopleCategoryRepoInterface::class,
            \App\DataServices\PeopleCategory\PeopleCategoryRepo::class
        );
        $this->app->singleton(
            \App\DataServices\File\FileRepoInterface::class,
            \App\DataServices\File\FileRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Region\RegionRepoInterface::class,
            \App\DataServices\Region\RegionRepo::class
        );
        $this->app->singleton(
            \App\DataServices\Page\PageRepoInterface::class,
            \App\DataServices\Page\PageRepo::class
        );
    }
}
