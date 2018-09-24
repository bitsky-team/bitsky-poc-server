<?php

    return [
        #   Type         Name                       Controller Method 
        [   'POST',      '/login',                 'auth',     'login'],
        [   'POST',      '/register',              'auth',     'register'],
        [   'POST',      '/auth_verify',           'auth',     'verify'],
        [   'POST',      '/register_confirmation', 'auth',     'checkRegisterConfirmation'],
        [   'POST',      '/get_firsttime',         'auth',     'getFirstTime'],
    ];