<?php

return [
    #   Type         Name                                 Controller       Method 
    [   'POST',      '/get_temp',                           'hardware',      'getTemp'                      ],
    [   'POST',      '/get_cpu',                            'hardware',      'getCPUUsage'                  ],

    [   'POST',      '/login',                              'auth',          'login'],
    [   'POST',      '/register',                           'auth',          'register'],
    [   'POST',      '/auth_verify',                        'auth',          'verify'],
    [   'POST',      '/register_confirmation',              'auth',          'checkRegisterConfirmation'    ],
    [   'POST',      '/get_firsttime',                      'auth',          'getFirstTime'                 ],

    [   'POST',      '/store_post',                         'post',          'store'                        ],
    [   'POST',      '/remove_post',                        'post',          'remove'                       ],
    [   'POST',      '/post_add_favorite',                  'post',          'addFavorite'                  ],
    [   'POST',      '/post_remove_favorite',               'post',          'removeFavorite'               ],
    [   'POST',      '/post_get_user_favorite',             'post',          'getFavoriteOfUser'            ],
    [   'POST',      '/get_post',                           'post',          'get'                          ],
    [   'POST',      '/get_post_score',                     'post',          'getScore'                     ],
    [   'POST',      '/get_allposts',                       'post',          'getAll'                       ],
    [   'POST',      '/get_trends',                         'post',          'getTrends'                    ],
    [   'POST',      '/get_allcomments',                    'post',          'getAllComments'               ],
    [   'POST',      '/get_commentscount',                  'post',          'getCommentsCount'             ],
    [   'POST',      '/get_bestcomments',                   'post',          'getBestComments'              ],
    [   'POST',      '/post_add_comment',                   'post',          'addComment'                   ],
    [   'POST',      '/post_remove_comment',                'post',          'removeComment'                ],
    [   'POST',      '/post_get_comments',                  'post',          'getComments'                  ],
    [   'POST',      '/post_get_user_comment_favorite',     'post',          'getCommentFavoriteOfUser'     ],
    [   'POST',      '/post_add_comment_favorite',          'post',          'addCommentFavorite'           ],
    [   'POST',      '/post_remove_comment_favorite',       'post',          'removeCommentFavorite'        ],

    [   'POST',      '/create_user',                        'user',          'create'                       ],
    [   'POST',      '/delete_user',                        'user',          'delete'                       ],                
    [   'POST',      '/get_allusers',                       'user',          'getAll'                       ],
    [   'POST',      '/get_user',                           'user',          'getById'                      ],

    [   'GET',       '/get_ranks',                          'rank',          'getAll'                       ],
];