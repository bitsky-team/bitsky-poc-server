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
            $_POST['token'] = $admin['token'];
            $_POST['owner_uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $_POST['content'] = 'Id aliqua quis eiusmod et anim et. Ipsum enim eiusmod est tempor exercitation sint aliquip ad mollit aliquip. Commodo nulla cupidatat culpa ullamco dolor ipsum ad esse. Officia est culpa eu non voluptate mollit ullamco ipsum deserunt laborum consectetur mollit sint. Quis incididunt culpa ut eu anim ut ullamco culpa excepteur ipsum exercitation ullamco excepteur duis. Deserunt sit nisi in anim cupidatat eiusmod laboris et consequat veniam sint aute duis. Ad voluptate quis ipsum eu consectetur Lorem minim ullamco ea ipsum nisi.';
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
            $_POST['token'] = $admin['token'];
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
            $_POST['token'] = $admin['token'];
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
            $_POST['token'] = $admin['token'];
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
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];

            // Get post
            $result = $postController->get();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(!is_null($result['post']));
        }

        public function testGetAll() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get posts
            $result = $postController->getAll();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(is_array($result['posts']));
        }

        public function testGetTrends() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['uniq_id'] = $admin['uniq_id'];

            // Get posts
            $result = $postController->getTrends();
            $result = json_decode($result, true);

            $this->assertTrue($result['success']);
            $this->assertTrue(is_array($result['trends']));
        }

        public function testAddComment() : void
        {
            Kernel::bootEloquent();
            $postController = new PostController();

            // Get Admin Account
            $admin = UserModel::where('rank', 2)->first();
            $_POST['token'] = $admin['token'];
            $_POST['owner_id'] = $admin['uniq_id'];

            // Preparing data
            $lastPost = PostModel::orderBy('created_at', 'desc')->first();
            $_POST['post_id'] = $lastPost['id'];
            $_POST['content'] = 'This is a comment';

            // Adding comment
            $result = $postController->addComment();
            
            $post = PostModel::find($lastPost['id']);
            $this->assertTrue($post->comments > $lastPost->comments);

            $result = json_decode($result, true);
            $this->assertTrue($result['success']);
        }
    }