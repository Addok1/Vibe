<?php

namespace App\Transformers\Common;

use App\Transformers\Transformer;
use App\Models\Admin\BannerImageModel;

class AppModuleBannerImageTransformer extends Transformer {
	/**
	 * Resources that can be included if requested.
	 *
	 * @var array
	 */
	protected array $availableIncludes = [

	];

	/**
	 * A Fractal transformer.
	 *
	 * @return array
	 */
	public function transform(BannerImageModel $bannerImageModel) {
		   return [
           
            // 'image'     => $bannerImageModel->image,
                // ? asset('storage/uploads/banner/banner-image/' . $bannerImageModel->image)
                // : null,
		    'preview_image' => $bannerImageModel->previewimage_url,
            'image_url' => $bannerImageModel->imageurl,

   			'active'    => $bannerImageModel->active,

			'module' => $bannerImageModel->appModule ? [
				'name' => $bannerImageModel->appModule->name,
				'transport_type' =>$bannerImageModel->appModule->transport_type,
				'service_type'=> $bannerImageModel->appModule->service_type,
			] : null,
         

        ];
	}

}
