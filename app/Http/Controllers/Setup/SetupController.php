<?php 

namespace App\Http\Controllers\Setup; 

use App\Http\Controllers\Controller; 
use App\Application\Setup\UseCases\SetupAppUseCase; 
use Illuminate\Support\Facades\Log; 

class SetupController extends Controller{
    public function __construct(
        private readonly SetupAppUseCase $setupAppUseCase
    )
    {}
    public function setup(){
        try{
            $this->setupAppUseCase->execute(); 
        }catch(\Exception $e){
            Log::error($e->getMessage()); 
        }   
    }
}