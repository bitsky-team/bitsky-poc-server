<?php

return [
    #   Type         Name                                 Controller       Method 
    [   'POST',      '/get_temp',                           'hardware',      'getTemp'],

    [   'POST',      '/login',                              'auth',          'login'],
    [   'POST',      '/register',                           'auth',          'register'],
    [   'POST',      '/auth_verify',                        'auth',          'verify'],
    [   'POST',      '/register_confirmation',              'auth',          'checkRegisterConfirmation'],
    [   'POST',      '/get_firsttime',                      'auth',          'getFirstTime'],

    [   'POST',      '/store_post',                         'post',          'store'],
    [   'POST',      '/remove_post',                        'post',          'remove'],
    [   'POST',      '/post_add_favorite',                  'post',          'addFavorite'],
    [   'POST',      '/post_remove_favorite',               'post',          'removeFavorite'],
    [   'POST',      '/post_get_user_favorite',             'post',          'getFavoriteOfUser'],
    [   'POST',      '/get_post',                           'post',          'get'],
    [   'POST',      '/get_allposts',                       'post',          'getAll'],
    [   'POST',      '/get_trends',                         'post',          'getTrends'],
    [   'POST',      '/get_allcomments',                    'post',          'getAllComments'],
    [   'POST',      '/get_commentscount',                  'post',          'getCommentsCount'],
    [   'POST',      '/get_bestcomments',                   'post',          'getBestComments'],
    [   'POST',      '/post_add_comment',                   'post',          'addComment'],

    [   'POST',      '/create_user',                        'user',          'create'],
    [   'POST',      '/delete_user',                        'user',          'delete'],                
    [   'POST',      '/get_allusers',                       'user',          'getAll'],

    [   'GET',       '/get_ranks',                          'rank',          'getAll'],
];