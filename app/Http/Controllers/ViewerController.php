<?php

namespace App\Http\Controllers;

use App\Models\RadiologyOrder;
use Illuminate\Http\Request;

class ViewerController extends Controller
{
    public function show(RadiologyOrder $order)
    {
        $order->load('studyMetadata', 'patient');

        if (!$order->studyMetadata || !$order->studyMetadata->PACS_id) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Study belum tersedia di PACS');
        }

        $pacsUrl = config('pacs.public_url'); // Use public url for viewer
        $studyId = $order->studyMetadata->PACS_id;

        // OHIF viewer URL or PACS built-in viewer
        $viewerUrl = "{$pacsUrl}/app/explorer.html#study?uuid={$studyId}";

        return view('viewer.show', compact('order', 'viewerUrl', 'pacsUrl', 'studyId'));
    }
}


