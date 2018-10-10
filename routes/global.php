<?php

    return [
        #   Type         Name                      Controller       Method 
        [   'POST',      '/login',                      'auth',          'login'],
        [   'POST',      '/register',                   'auth',          'register'],
        [   'POST',      '/auth_verify',                'auth',          'verify'],
        [   'POST',      '/register_confirmation',      'auth',          'checkRegisterConfirmation'],
        [   'POST',      '/get_firsttime',              'auth',          'getFirstTime'],
        
        [   'POST',      '/store_post',                 'post',          'store'],
        [   'POST',      '/remove_post',                'post',          'remove'],
        [   'POST',      '/post_add_favorite',          'post',          'addFavorite'],
        [   'POST',      '/post_remove_favorite',       'post',          'removeFavorite'],
        [   'POST',      '/post_get_user_favorite',     'post',          'getFavoriteOfUser'],
        [   'POST',      '/get_allposts',               'post',          'getAll'],
        [   'POST',      '/get_trends',                 'post',          'getTrends'],
    ];