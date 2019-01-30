<?php

    namespace Controller;
    
    use \Kernel\LogManager;
    use \Model\Rank as RankModel;

    class Rank extends Controller
    {
        public function getAll() 
        {
            $ranks = RankModel::all();
            return json_encode(['success' => true, 'ranks' => $ranks]);
        }
    }