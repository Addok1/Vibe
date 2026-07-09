<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Promotion\PromotionTemplate;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Filters\Admin\PromotionTemplateFilter;
use Illuminate\Support\Facades\Storage;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PromotionController extends Controller
{

    protected $imageUploader;

    public function __construct(ImageUploaderContract $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }

    public function index()
    {
        return Inertia::render('pages/Promotion/index');
    }

    public function list(QueryFilterContract $queryFilter)
    {
        $query = PromotionTemplate::query();

        $results = $queryFilter
            ->builder($query)
            ->customFilter(new PromotionTemplateFilter)
            ->paginate();

            $results->getCollection()->transform(function ($item) {
                $item->preview_image_url = $item->preview_image
                    ? asset('storage/' . $item->preview_image)
                    : null;
                return $item;
            });

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }

    public function create()
    {
        return Inertia::render('pages/Promotion/create', [
            'template' => null
        ]);
    }

    public function store(Request $request)
    {
        // ✅ ASSIGN validation result
        $validatedData = $request->validate([
            'subject' => 'required',
            'html' => 'required',
            'preview_image' => 'required',
            'date' => 'required|string',
            'time' => 'nullable|integer|min:0',
        ]);
    
        // ✅ parse date range
        $dateRange = explode(' to ', $validatedData['date']);
    
        $fromDate = Carbon::parse(trim($dateRange[0]))
            ->startOfDay()
            ->toDateTimeString();
    
        $toDate = Carbon::parse(trim($dateRange[1]))
            ->endOfDay()
            ->toDateTimeString();
    
        // ✅ store template
        $template = PromotionTemplate::create([
            'subject' => $validatedData['subject'],
            'html' => $validatedData['html'],
            'from' => $fromDate,
            'to' => $toDate,
            'time' => $validatedData['time'] ?? null,
            'active' => true,
            // 'created_by' => auth()->id(),
        ]);
    
        // ✅ store preview image
        $this->savePreview($template, $validatedData['preview_image']);
    
        return response()->json([
            'successMessage' => 'Template created successfully',
            'template' => $template
        ], 201);
    }

    public function edit($id)
    {
        $template = PromotionTemplate::findOrFail($id);

        return Inertia::render('pages/Promotion/create', [
            'template' => $template
        ]);
    }

    public function update(Request $request, PromotionTemplate $template)
    {
        $request->validate([
            'subject' => 'required',
            'html' => 'required',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'time' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $template->update([
            'subject' => $request->subject,
            'html' => $request->html,
            'from' => $request->from,
            'to' => $request->to,
            'time' => $request->time,
            'active' => $request->active,

        ]);

        if ($request->preview_image) {
            $this->savePreview($template, $request->preview_image);
        }

        return response()->json([
            'successMessage' => 'Template updated successfully',
            'template' => $template
        ], 201);
    }

    public function toggleActive(Request $request, PromotionTemplate $template)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        if (
            $request->status === false &&
            Carbon::now()->greaterThan($template->to)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate expired promotion',
            ], 422);
        }
    
        $template->update([
            'active' => $request->status
        ]);
    
        return response()->json([
            'success' => true,
            'active' => $template->active
        ]);
    }

    public function destroy(PromotionTemplate $template)
    {
        $template->delete();

        return response()->json([
            'successMessage' => 'Template deleted successfully'
        ]);
    }

    // private function savePreview($template, $base64)
    // {
    //     $image = str_replace('data:image/png;base64,', '', $base64);
    //     $image = base64_decode($image);

    //     $path = "promotion/previews/template_{$template->id}.png";
    //     Storage::disk('public')->put($path, $image);

    //     $template->update(['preview_image' => $path]);
    // }

    private function savePreview(PromotionTemplate $template, string $base64)
{
    // convert base64 to temporary file
    $imageData = base64_decode(
        preg_replace('#^data:image/\w+;base64,#i', '', $base64)
    );

    $tmpPath = sys_get_temp_dir() . '/preview_' . uniqid() . '.png';
    file_put_contents($tmpPath, $imageData);

    // convert to UploadedFile
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $tmpPath,
        'preview.png',
        'image/png',
        null,
        true
    );

    // upload via ImageUploader
    $filename = $this->imageUploader
        ->file($uploadedFile)
        ->savePromotionPreview();

    // store filename in DB
    $template->update([
        'preview_image' => $filename
    ]);

    unlink($tmpPath);
}
}
