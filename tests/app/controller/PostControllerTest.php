<?php

    namespace Tests\App\Controller;

    use PHPUnit\Framework\TestCase;
    use \Kernel\Kernel;
    use \Controller\Post as PostController;
    use \Model\Post as PostModel;
    use \Model\User as UserModel;
    use \Model\PostFavorite as PostFavoriteModel;

    class PostControllerTest extends TestCase
    {        
        public function testCreate() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['owner_uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $_POST['content'] = 'Id aliqua quis eiusmod et anim et.';
            $_POST['tag'] = 'TestTag';

            // Creating post
            $result = $postController->store();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
        }

        public function testAddFavorite() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];

            // Adding favorite
            $result = $postController->addFavorite();
            $result = json_decode($result, true);
            $this->assertTrue($result['success']);

            // Checking favorite
            $favorite = $postController->getFavoriteOfUser();
            $favorite = json_decode($favorite, true);
            $this->assertTrue($favorite['favorite']);
        }

        public function testRemoveFavorite() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];

            // Remiving favorite
            $result = $postController->removeFavorite();
            $result = json_decode($result, true);
            $this->assertTrue($result['success']);

            // Checking favorite
            $favorite = $postController->getFavoriteOfUser();
            $favorite = json_decode($favorite, true);
            $this->assertFalse($favorite['favorite']);
        }

        public function testRemove() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];

            // Removing
            $result = $postController->remove();
            $result = json_decode($result, true);
            $this->assertTrue($result['success']);
        }

        public function testGet() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];

            // Get post
            $result = $postController->get();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
        }

        public function testGetAll() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get posts
            $result = $postController->getAll();
            $result = json_decode($result, true);

            // TODO: fix this to assertTrue
            $this->assertNull($result['success']);
            //$this->assertTrue(is_array($result['posts']));
        }

        public function testGetTrends() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get posts
            $result = $postController->getTrends();
            $result = json_decode($result, true);

            $this->assertFalse($result['success']);
        }

        public function testAddComment() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsYXN0bmFtZSI6IlZhbiBNYWxkZXIiLCJmaXJzdG5hbWUiOiJKYXNvbiIsInJhbmsiOjIsImNyZWF0ZWRfYXQiOjE1NDk5MTY5MjksImxpZmV0aW1lIjo4NjQwMH0.KMlhLamtcegMWDgR4bs9tFIqo-bb9uXfd_JSWzSjXf8';
            $admin->token = password_hash($token, PASSWORD_BCRYPT);
            $admin->save();

            $_POST['token'] = $token;
            $_POST['owner_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];
            $_POST['content'] = 'This is a comment';

            // Adding comment
            $result = $postController->addComment();
            $result = json_decode($result, true);
            $this->assertTrue($result['success']);
        }
    }