<?php

    return [
        #   Type        Name            Controller   Method 
        [   'GET',      '/',            'home',      'index'],
        [   'GET',      'home',         'home',      'index'],
        [   'GET',      'home/{id}',    'home',      'index'],        
        [   'POST',     '/',            'home',      'postit'],
        [   'POST',     'home',         'home',      'postit'],
    ];