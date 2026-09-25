<?php

namespace App\Controllers;

use App\Models\BoxModel;
use App\Models\PhotoModel;
use CodeIgniter\Files\File;

class Photo extends BaseController
{
    /** Foto uitserveren — gescoped op de actieve verhuizing (handoff.md §6). */
    public function show(int $id)
    {
        $photo = (new PhotoModel())->find($id);
        $box   = $photo ? (new BoxModel())->find((int) $photo['box_id']) : null;
        if (! $box) {
            return $this->response->setStatusCode(404);
        }

        $path = PhotoModel::dirFor($box) . '/' . basename($photo['bestandsnaam']);
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
