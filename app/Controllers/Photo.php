<?php

namespace App\Controllers;

use App\Models\PhotoModel;
use CodeIgniter\Files\File;

class Photo extends BaseController
{
    public function show(int $id)
    {
        $photo = (new PhotoModel())->find($id);
        if (! $photo) {
            return $this->response->setStatusCode(404);
        }

        $path = WRITEPATH . 'uploads/boxes/' . $photo['box_id'] . '/' . $photo['bestandsnaam'];
        if (! is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        $file = new File($path);

        return $this->response
            ->setHeader('Content-Type', $file->getMimeType())
            ->setHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->setBody(file_get_contents($path));
    }
}
