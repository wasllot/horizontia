<?php

use Modules\Courses\Services\CourseService;
use Illuminate\Support\Facades\Storage;


/**
 * Upload base64 image into custom storage folder.
 *
 * @param string $dirName   Required. Directory name
 * @param string $imageUrl  Required. Base64 image string
 * @return array The process of array record
 */
if (! function_exists('uploadBase64Image')) {

    function uploadBase64Image($dirName, $name, $imageUrl)
    {

        $file_ext = ".png";
        $imageName = $name;
    
        // Get the storage disk dynamically
        $disk = getStorageDisk();
    
        // Check if the file already exists and generate a unique name if necessary
        $i = 0;
        while (Storage::disk($disk)->exists($dirName . '/' . $imageName . $file_ext)) {
            $i++;
            $imageName = preg_replace('/\(\d+\)$/', '', $name) . '(' . $i . ')';
        }
    
        $fileName = $imageName . $file_ext;
        $filePath = $dirName . '/' . $fileName;
    
        // Decode the base64 image
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageUrl));
        if ($image === false) {
            throw new \Exception("Failed to decode base64 image");
        }
    
        // Save the image file to the specified disk
        $storeFile = Storage::disk($disk)->put($filePath, $image);
        if ($storeFile === false) {
            throw new \Exception("Failed to save image file to disk: $filePath");
        }
    
        if ($storeFile) {
            return $filePath; // Return the full path to the saved file
        }
    
        return '';
    }
}


/**
*return pagination select options
*
* @return response()
*/
if (!function_exists('perPageOpt')) {

    function perPageOpt()
    {

        return [10, 20, 30, 50, 100, 200];
    }
}

/**
 * Get course duration in hours and minutes 
 * @param int $contentLength
 * @return string
 */
if (!function_exists('getCourseDuration')) {
    function getCourseDuration($contentLength)
    {
        if ($contentLength == 0) {
            return '';
        }

        $hours = floor($contentLength / 3600);
        $minutes = floor(($contentLength % 3600) / 60);
        $seconds = $contentLength % 60;

        $duration = '';

        if ($hours > 0) {
            $duration .= $hours . ' ' . ($hours > 1 ? __('courses::courses.hrs') : __('courses::courses.hr'));
        }

        if ($minutes > 0) {
            $duration .= ($duration ? ' : ' : '') . $minutes . ' ' . ($minutes > 1 ? __('courses::courses.mins') : __('courses::courses.min'));
        }

        if ($seconds > 0) {
            $duration .= ($duration ? ' : ' : '') . $seconds . ' ' . __('courses::courses.sec');
        }

        return $duration;
    }
}

/**
 * Get course duration in hours and minutes 
 * @param int $contentLength
 * @return string
 */
if (!function_exists('getCourseDurationWithoutSecond')) {
    function getCourseDurationWithoutSecond($contentLength)
    {
        if ($contentLength == 0) {
            return '';
        }

        $hours      = floor($contentLength / 3600);
        $minutes    = floor(($contentLength % 3600) / 60);
        $duration   = '';

        if ($hours > 0) {
            $duration .= $hours . ' ' . __('courses::courses.h');
        }

        if ($minutes > 0) {
            $duration .= $minutes . ' ' . __('courses::courses.m');
        }

        return $duration;
    }
}

if(!function_exists('courseMenuOptions')) {
    function courseMenuOptions($role)
    {
        switch ($role) {
            case 'tutor':
                return [
                    [
                        'tutorSortOrder' => 4,
                        'route' => 'courses.tutor.courses',
                        'onActiveRoute' => ['courses.tutor.courses', 'courses.tutor.edit-course', 'courses.tutor.create-course'],
                        'title' => __('courses::courses.manage_courses'),
                        'icon'  => '<i class="am-icon-book-1"></i>',
                        'accessibility' => ['tutor'],
                        'disableNavigate' => true,
                    ],
                    [
                        'tutorSortOrder' => 5,
                        'route' => 'courses.tutor.schedule-live-stream',
                        'onActiveRoute' => ['courses.tutor.schedule-live-stream'],
                        'title' => 'Programar En Vivo',
                        'icon'  => '<i><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#585858" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg></i>',
                        'accessibility' => ['tutor'],
                        'disableNavigate' => true,
                    ],
                    [
                        'tutorSortOrder' => 6,
                        'route' => 'courses.tutor.manage-live-streams',
                        'onActiveRoute' => ['courses.tutor.manage-live-streams'],
                        'title' => 'Mis Sesiones en Vivo',
                        'icon'  => '<i><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#585858" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7"></path></svg></i>',
                        'accessibility' => ['tutor'],
                        'disableNavigate' => true,
                    ],
                    [
                        'tutorSortOrder' => 7,
                        'route' => 'courses.tutor.assignments',
                        'onActiveRoute' => ['courses.tutor.assignments'],
                        'title' => __('courses::courses.assignments') ?? 'Tareas y Trabajos',
                        'icon'  => '<i><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#585858" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></i>',
                        'accessibility' => ['tutor'],
                        'disableNavigate' => true,
                    ]
                ];
                break;
            case 'student':
                return [
                    [
                        'route' => 'courses.course-list',
                        'studentSortOrder' => 3,
                        'onActiveRoute' => ['courses.course-list'],
                        'title' => __('courses::courses.my_learning'),
                        'icon'  => '<i class="am-icon-book-1"></i>',
                        'accessibility' => ['student'],
                        'disableNavigate' => true,
                    ],
                    [
                        'route' => 'courses.search-courses',
                        'studentSortOrder' => 5,
                        'onActiveRoute' => ['courses.search-courses'],
                        'title' => __('courses::courses.find_courses'),
                        'icon'  => '<i class="am-icon-book"></i>',
                        'accessibility' => ['student'],
                        'disableNavigate' => true,
                    ],
                ];
                break;
            case 'admin':

            $routes =  [
                'courses.admin.courses' => __('courses::courses.all_courses'),
                'courses.admin.categories' => __('courses::courses.categories'),
                'courses.admin.course-enrollments' => __('courses::courses.course_enrollments'),
            ];

            if ((function_exists('isPaidSystem') && isPaidSystem()) || !function_exists('isPaidSystem')) {
                $routes['courses.admin.commission-setting'] =  __('courses::courses.commission_settings');
            }
            return [
                [
                    'title' =>  __('courses::courses.manage_courses'),
                    'icon'  => 'icon-book-open',
                    'routes' => $routes,
                ]
            ];
            break;
            default:
                return [];
        }
    }
}

if(!function_exists('getFeaturedCourses')) {
    function getFeaturedCourses($userId = null)
    {
        return (new CourseService())->getFeaturedCourses($userId);
    }
}
if (!function_exists('getVideoUrl')) {
    function getVideoUrl($curriculum) {
        if ($curriculum->type === 'yt_link') {
            // Extract YouTube video ID
            $videoId = '';
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $curriculum->media_path, $matches);
            if(isset($matches[1])) {
                $videoId = $matches[1];
            }
            return isset($videoId) ? 'https://www.youtube.com/embed/' . $videoId : '';
        } elseif ($curriculum->type === 'vm_link') {
            // Extract Vimeo video ID
            $videoId = '';
            preg_match('/vimeo\.com\/(\d+)/', $curriculum->media_path, $matches);
            if(isset($matches[1])) {
                $videoId = $matches[1];
            }
            return isset($videoId) ? 'https://player.vimeo.com/video/' . $videoId : '';
        } else {
            // MP4 file

            return Storage::url($curriculum->media_path);
        }
   }
}
