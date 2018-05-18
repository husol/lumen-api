<?php

namespace App\Http\Controllers;

use App\DataServices\Company\CompanyRepoInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\File;
use App\Error;
use App\Common;

class CompanyController extends Controller
{
    protected $repoPeople;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CompanyRepoInterface $repoCompany)
    {
        $this->repoCompany = $repoCompany;
    }

    public function getDetail(Request $request, $id)
    {
        $err = new Error();
        $company = $this->repoCompany->getCompany($id);
        if (is_null($company)) {
            $err->setError('not_found', "Not found company with id = $id");
            return responseJson($err->getErrors(), 404);
        }
        $objCompany = (object) $company->toArray();

        //Adjust data company info
        //For logo
        if (!empty($objCompany->logo)) {
            $objCompany->logo = Common::getImgUrl() . convertImageUrlByType($objCompany->logo, 'medium');
        }

        //For avg_rating
        $objCompany->avg_rating = floatval($objCompany->avg_rating);
        $objCompany->avg_rating_rounded = round($objCompany->avg_rating*2)/2;

        //For share_link
        $tmpName = codau2khongdau($objCompany->name, true);
        $objCompany->share_link = env('WEB_ROOTURL')."/company/{$tmpName}-$objCompany->id";

        $postedJobTotal = $this->repoCompany->getPostedJobTotal($objCompany->id);
        $objCompany->posted_job_total = intval($postedJobTotal);

        //Increase countview
        $this->repoCompany->update($objCompany->id, ['countview' => ++$objCompany->countview]);

        return responseJson($objCompany);
    }

    public function update(Request $request)
    {
        $loggedUser = Common::getLoggedUserInfo();

        $this->validate($request, [
            'name' => 'required|min:3|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'nullable|email',
            'description' => 'nullable|string'
        ]);

        $dataUpdated = [];
        $dataUpdated['name'] = $request->input('name');
        $dataUpdated['phone'] = $request->input('phone');
        $dataUpdated['address'] = $request->input('address');
        $dataUpdated['website'] = $request->input('website');
        $dataUpdated['email'] = $request->input('email');
        $dataUpdated['description'] = $request->input('description');

        $err = new Error();
        $myCompany = $this->repoCompany->getByUserId($loggedUser->id);

        if (empty($myCompany)) {
            $dataUpdated['id_user'] = $loggedUser->id;
            $company = $this->repoCompany->create($dataUpdated);
        } else {
            $company = $this->repoCompany->update($myCompany->id, $dataUpdated);
        }

        if ($request->hasFile('logo')) {
            if (!$request->file('logo')->isValid()) {
                $err->setError('error_logo_uploaded', "Logo cannot be uploaded");
                return responseJson($err->getErrors(), 501);
            }
            $logo = $request->file('logo');

            //Start upload logo
            $sourceFile = ['file'=> $logo->getPathname(), 'name' => $logo->getClientOriginalName()];
            $result = Common::uploadImage($sourceFile);
            if ($result['error'] == 0) {
                //Delete old logo image
                if (!empty($company->logo)) {
                    $tmpLink = convertImageUrlByType($company->logo, "small");
                    $fileObj = new File($tmpLink);
                    $fileObj->delete();
                    $tmpLink = convertImageUrlByType($company->logo, "medium");
                    $fileObj = new File($tmpLink);
                    $fileObj->delete();
                    $fileObj = new File($company->logo);
                    $fileObj->delete();
                }
                $company = $this->repoCompany->update($company->id, ['logo' => $result['path']]);
                $company->logo = Common::getImgUrl() . convertImageUrlByType($company->logo, 'medium');
            } else {
                $err->setError('failed_upload_logo', $result['info']);
                return responseJson($err->getErrors(), 501);
            }
        }

        return responseJson($company);
    }
}
