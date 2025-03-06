<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExampleController extends Controller
{
   
        /**
         * Store .
         *
         * @param Request $request
         * @return \Illuminate\Http\JsonResponse
         */
        public function store(Request $request)
        {
            // Check if the user has the 'add-zone' permission
            if (!$request->user()->hasPermission('add-zone')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
    
            //required logic 
    


            return response()->json([
                'message' => ' successfully added ',
           
            ], 201);
        }
    
        /**
         * Update an existing zone.
         *
         * @param Request $request
   
         * @return \Illuminate\Http\JsonResponse
         */
        public function update(Request $request)
        {
            // Check if the user has the 'edit-zone' permission
            if (!$request->user()->hasPermission('edit-zone')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
    
             //required logic 
    


    
            return response()->json([
                'message' => ' updated successfully.',
              
            ]);
        }
    
        /**
         * Delete .
       
         * @return \Illuminate\Http\JsonResponse
         */
        public function destroy(Request $request)
        {
            // Check if the user has the 'delete-zone' permission
            if (!$request->user()->hasPermission('delete-zone')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
    
          //required logic 
    



            return response()->json([
                'message' => ' deleted successfully.',
            ]);
        }
    }

