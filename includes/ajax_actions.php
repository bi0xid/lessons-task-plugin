<?php

class ajax_actions {

	private $table_name;
	private $task_helpers;
	private $sensei_helpers;

	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'tasks';
		$this->task_helpers = new task_helpers();
		$this->sensei_helpers = new sensei_helpers();

		add_action( 'wp_ajax_add_lesson_task', array( $this, 'add_lesson_task' ) );
		add_action( 'wp_ajax_mark_task_as_done', array( $this, 'mark_task_as_done' ) );
		add_action( 'wp_ajax_is_all_tasks_done', array( $this, 'is_all_tasks_done' ) );
		add_action( 'wp_ajax_remove_lesson_task', array( $this, 'remove_lesson_task' ) );
		add_action( 'wp_ajax_update_lesson_task', array( $this, 'update_lesson_task' ) );
		add_action( 'wp_ajax_get_todo_modal_data', array( $this, 'get_todo_modal_data' ) );
		add_action( 'wp_ajax_mark_task_as_opened', array( $this, 'mark_task_as_opened' ) );
		add_action( 'wp_ajax_remove_user_todo_stats', array( $this, 'remove_user_todo_stats' ) );
		add_action( 'wp_ajax_mark_special_todo_done', array( $this, 'mark_special_todo_done' ) );
	}

	/**
	 * Remove ToDos stats for a specific user
	 * @param: (int) $todo_id
	 * @param: (int) $course_id
	 * @return: (object)
	 */
	public function mark_special_todo_done() {
		$todo_id   = $_POST['todo_id'];
		$user_id   = get_current_user_id();
		$course_id = (int) $_POST['course_id'];

		$user_special_todos_done = get_user_meta( $user_id, 'special_todo_done', true );

		// If the user is new lets prepare the Meta Data
		if(!$user_special_todos_done || gettype($user_special_todos_done) == 'string') {
			$user_special_todos_done = [];
			$user_special_todos_done[$course_id] = array(
				$todo_id => true
			);
		}
		// Else lets add the ToDo as done for the user
		else {
			$user_special_todo_done = $user_special_todos_done[$course_id];
			$user_special_todo_done[$todo_id] = true;

			$user_special_todos_done[$course_id] = $user_special_todo_done;
		}


		update_user_meta( $user_id, 'special_todo_done', $user_special_todos_done );

		// Handler if the special ToDo is done
		$lesson_id = $this->sensei_helpers->get_first_course_lesson($course_id);
		$lesson_done = $this->sensei_helpers->is_all_lessons_tasks_done($lesson_id)
			? WooThemes_Sensei_Utils::update_lesson_status($user_id, $lesson_id, 'complete')
			: false;

		// Record Activity for this ToDo
		$username  = get_userdata( $user_id )->user_login;
		$todo_name = get_post_meta( $course_id, 'course_'.$todo_id, true )['name'];
		$activity_id = bp_activity_add(array(
			'action'    => '<a href="#" target="_blank">'.$username.'</a> completed'.$todo_name.'.',
			'content'   => 'just complete '.$todo_name,
			'component' => 'todos_component',
			'item_id'   => $lesson_id,
			'type'      => 'todo_completed',
		));

		echo json_encode(array(
			'lesson_done' => $lesson_done,
			'progress'    => $this->task_helpers->get_lesson_task_progress($lesson_id, $course_id)
		));

		die();
	}

	/**
	 * Remove ToDos stats for a specific user or all users
	 * @param: (int) $user_id
	 * @param: (boolean) $all_users
	 */
	public function remove_user_todo_stats() {
		$user_id = (int) $_POST['user_id'];

		// Reset ToDos for All users
		if(isset($_POST['all_users'])) {
			$users = get_users( array(
				'blog_id' => $GLOBALS['blog_id'],
			) );

			foreach ($users as $user) {
				delete_user_meta( $user->ID, 'Done Tasks' );
				delete_user_meta( $user->ID, 'Done Lessons' );
				delete_user_meta( $user->ID, 'Opened Tasks' );
				delete_user_meta( $user->ID, 'Done Task Time' );
				delete_user_meta( $user->ID, 'special_todo_done' );
			}
		}
		// Reset only for one user
		elseif(isset($user_id)) {
			delete_user_meta( $user_id, 'Done Tasks' );
			delete_user_meta( $user_id, 'Done Lessons' );
			delete_user_meta( $user_id, 'Opened Tasks' );
			delete_user_meta( $user_id, 'Done Task Time' );
			delete_user_meta( $user_id, 'special_todo_done' );
		}

		die();
	}

	/**
	 * Remove task
	 * @param: (int) $task_id | (int) $lesson_id
	 * @return: (obj) sql result state
	 */
	public function remove_lesson_task() {
		global $wpdb;

		$task_id   = (int) $_POST['task_id'];
		$lesson_id = (int) $_POST['lesson_id'];

		// Eliminamos de Base de Datos
		$remove = $wpdb->query($wpdb->prepare(
			"
				DELETE FROM $this->table_name
				WHERE task_id = %d
			", $task_id
		));

		// Desvinculamos de los meta de Lesson
		$lesson_meta_task = get_post_meta($lesson_id, 'Lesson Tasks', true);
		if (($key = array_search((int) $task_id, $lesson_meta_task)) !== false && $remove) {
			unset($lesson_meta_task[$key]);
		}

		update_post_meta($lesson_id, 'Lesson Tasks', $lesson_meta_task);

		// Desvinculamos de los meta de los usuarios del curso
		$course_id = get_post_meta($lesson_id, '_lesson_course', true);
		$activity_args = array(
			'post_id' => $course_id,
			'type'    => 'sensei_course_status',
			'status'  => 'in-progress',
		);

		$user_statusses = WooThemes_Sensei_Utils::sensei_check_for_activity($activity_args, true);

		$users = array();
		foreach($user_statusses as $activity){
			$users[] = get_user_by('id', $activity->user_id);
		}

		foreach ($users as $user) {
			$user_opened_tasks      = get_user_meta($user->ID, 'Opened Tasks')[0];
			$user_done_tasks        = get_user_meta($user->ID, 'Done Tasks')[0];
			$user_opened_tasks_time = get_user_meta($user_id, 'Done Task Time')[0];

			// Remove the task from the User Opened Tasks
			if($user_opened_tasks) {
				$result_opened = array();
				foreach ($user_opened_tasks as $user_opened_task) {
					if($user_opened_task !== $task_id) {
						$result_opened[] = $user_opened_task;
					}	
				}
				update_user_meta($user->ID, 'Opened Tasks', $result_opened);
			}

			// Remove the task from the User Done Tasks
			if($user_done_tasks) {
				$result_done = array();
				foreach ($user_done_tasks as $user_done_task) {
					if($user_done_task !== $task_id) {
						$result_done[] = $user_done_task;
					}	
				}
				update_user_meta($user->ID, 'Done Tasks', $result_done);
			}

			// Remove the task from the User Done Tasks Time
			if($user_opened_tasks_time) {
				$result_time = array();
				foreach ($user_opened_tasks_time as $user_opened_task_time) {
					if($user_opened_tasks_time['task_id'] !== $task_id) {
						$result_time[] = array(
							'task_id' => $task_id,
							'time'    => date('d/m/Y h:i', time())
						);
					}	
				}
				update_user_meta($user->ID, 'Done Task Time', $result_time);
			}
		}

		echo json_encode(array(
			'status' => $remove,
			'error'  => $wpdb->last_error
		));

		die();
	}

	/**
	 * Add task
	 * @param: (int) $lesson_id
	 * @param: (int) $task_points
	 * @param: (int) $task_type
	 * @param: (string) $task_desc
	 * @param: (string) $task_name
	 * @param: (string) $task_video
	 * @return: (obj) sql result state
	 */
	public function add_lesson_task() {
		global $wpdb;

		$task_name   = $_POST['name'];
		$task_points = $_POST['points'];
		$task_type   = (int) $_POST['type'];
		$lesson_id   = (int) $_POST['lesson_id'];

		// Each ToDo have a different Content
		$task_content = undefined;
		switch ($task_type) {
			case 0: case 2: case 3:
				// Serialize de Content in case of iframes
				$content_unserialize = $_POST['content'];
				foreach ($content_unserialize as $key => $value) {
					if(isset($content_unserialize[$key]['desc'])) {
						$content_unserialize[$key]['desc'] = stripslashes(html_entity_decode($value['desc']));
					}
				}

				$task_content = serialize($content_unserialize);
				break;

			case 1:
				$content_unserialize = $_POST['content'];
				foreach ($content_unserialize as $key => $value) {
					// In this ToDo content is only saving images, lets skip
					if($key !== 'content') {
						$content_unserialize[$key]['desc'] = stripslashes(html_entity_decode($value['desc']));
					}
				}

				$task_content = serialize($content_unserialize);
				break;
		}


		// Procreed to save into DB
		$sql = $wpdb->query($wpdb->prepare(
			"
				INSERT INTO $this->table_name
				( name, type, content, points )
				VALUES ( %s, %s, %s, %s )
			",
			$task_name,
			$task_type,
			$task_content,
			$task_points
		));

		// Guardamos la Task en los meta del Lesson
		$lesson_meta_task = get_post_meta($lesson_id, 'Lesson Tasks', true);
		$task_id = (int) $this->task_helpers->get_last_task_id();
		$lesson_meta_task[] = $task_id;

		update_post_meta($lesson_id, 'Lesson Tasks', $lesson_meta_task);

		echo json_encode(array(
			'status'  => $sql,
			'task_id' => $task_id,
			'error'   => $wpdb->last_error
		));

		die();
	}

	/**
	 * Update task
	 * @param: (int) $task_id | (string) $name | (string) $description  | (int) $points
	 * @return: (obj) sql result state
	 */
	public function update_lesson_task() {
		global $wpdb;

		$task_type   = (int) $_POST['type'];
		$task_id     = (int) $_POST['task_id'];
		$task_name   = $_POST['name'];
		$task_points = $_POST['points'];

		$task_content = undefined;
		switch ($task_type) {
			case 0: case 2: case 3:
				// Serialize de Content in case of iframes
				$content_unserialize = $_POST['content'];
				foreach ($content_unserialize as $key => $value) {
					if(isset($content_unserialize[$key]['desc'])) {
						$content_unserialize[$key]['desc'] = stripslashes(html_entity_decode($value['desc']));
					}
				}
				$task_content = serialize($content_unserialize);
				break;

			case 1:
				$content_unserialize = $_POST['content'];
				foreach ($content_unserialize as $key => $value) {
					// In this ToDo content is only saving images, lets skip
					if($key !== 'content') {
						$content_unserialize[$key]['desc'] = stripslashes(html_entity_decode($value['desc']));
					}
				}

				$task_content = serialize($content_unserialize);
				break;
		}

		$sql = $wpdb->query($wpdb->prepare(
			"
				UPDATE $this->table_name SET
				name = %s,
				content = %s,
				points = %d
				WHERE task_id = %s;
			",
			$task_name,
			$task_content,
			$task_points,
			$task_id
		));

		echo json_encode(array(
			'status' => $sql,
			'error'  => $wpdb->last_error
		));

		die();
	}

	/**
	 * Mark task as opened
	 * This will add the task to the user meta information about tasks opened.
	 * Also we check the current status of the lesson for the current user.
	 * @param: (int) $task_id | (int) $lesson_id
	 * @return: (obj) sql result state | (string) If the task is now saved or was already
	 */
	public function mark_task_as_opened() {
		$user_id = get_current_user_id();

		$this->task_helpers->clear_user_meta_tasks($user_id);
		$this->task_helpers->check_user_lesson_status((int) $_POST['lesson_id']);

		$current_user_taks_opened = get_user_meta($user_id, 'Opened Tasks')[0];

		$is_already_opened = false;
		$status = 'already_opened';
		foreach ($current_user_taks_opened as $task_opened) {
			if($task_opened == (int) $_POST['task_id']) {
				$is_already_opened = true;
			}
		}

		if(!$is_already_opened) {
			$status = 'task_open';
			$current_user_taks_opened[] = (int) $_POST['task_id'];
		}

		update_user_meta($user_id, 'Opened Tasks', $current_user_taks_opened);

		echo json_encode(array('status' => $status));

		die();
	}

	/**
	 * Mark task as Done
	 * @param: (int) $task_id
	 * @return: (bool) If lesson is done | (string) task done status
	 */
	public function mark_task_as_done() {
		global $woothemes_sensei, $bp;

		$user_id   = get_current_user_id();
		$task_id   = (int) $_POST['task_id'];
		$lesson_id = (int) $_POST['lesson_id'];
		$now       = date('d-m-Y H:i', time());
		$username  = get_userdata($user_id)->user_login;
		$course_id = get_post_meta($lesson_id, '_lesson_course', true);

		// Lets check if the Task can be done
		$can_be_done = $this->task_helpers->todo_can_be_done($task_id) && !$this->task_helpers->is_task_done_by_user($task_id);

		if($can_be_done) {
			// Update current user Meta about Done tasks
			$status = 'already_done';
			$current_user_taks_done = get_user_meta($user_id, 'Done Tasks', true);

			if(!$this->find_id_inside_array($task_id, $current_user_taks_done)) {
				$status = 'task_done';
				$current_user_taks_done[] = $task_id;
			}
			update_user_meta($user_id, 'Done Tasks', $current_user_taks_done);

			// Add time when uses did this ToDo
			$user_todo_times = get_user_meta($user_id, 'Done Task Time', true);

			$exist = false;
			if($user_todo_times) {
				foreach ($user_todo_times as $user_todo_time) {
					if($user_todo_time['task_id'] == $task_id) {
						$exist = true;
					}
				}
			}
			if(!$exist) {
				$user_todo_times[] = array(
					'task_id' => $task_id,
					'time'    => $now
				);
			}

			update_user_meta($user_id, 'Done Task Time', $user_todo_times);

			// Record Activity for this task
			$activity_id = bp_activity_add(array(
				'action'    => '<a href="#" target="_blank">'.$username.'</a> completed '.$this->task_helpers->get_task_name($task_id).' ToDo.',
				'content'   => 'just complete '.$this->task_helpers->get_task_name($task_id),
				'component' => 'todos_component',
				'item_id'   => $lesson_id,
				'type'      => 'todo_completed',
			));

			// If all tasks are done we set the lesson as done
			$lesson_done = $this->sensei_helpers->is_all_lessons_tasks_done($lesson_id)
				? WooThemes_Sensei_Utils::update_lesson_status($user_id, $lesson_id, 'complete')
				: false;

			// Get if the lessons module is done
			$lesson_module = $this->sensei_helpers->get_lesson_module($lesson_id, $course_id);
			$module_progress = $woothemes_sensei->modules->get_user_module_progress($lesson_module->term_id, $course_id, $user_id);

			$is_module_done = $module_progress >= 100 && $lesson_done;

			// Get if the course is Done
			$course_stats     = $this->sensei_helpers->get_course_progress_percentage($course_id, $user_id);
			$course_done_page = get_permalink( get_page_by_path( 'finished-course-page' ) ).'?post_id='.$course_id;

			echo json_encode(array(
				'status'               => $status,
				'lesson_done'          => $lesson_done,
				'course_done'          => $course_stats >= 100,
				'module_done'          => $is_module_done,
				'course_finished_page' => $course_stats >= 100 ? $course_done_page : undefined,
				'lesson_todo_progress' => $this->task_helpers->get_lesson_task_progress($lesson_id, $course_id)
			));
		}
		else {
			echo json_encode(array('can_done_todo' => false));
		}

		die();
	}

	/**
	 * Get if the ToDo can be Done and returns proper object
	 * @param: (int) $task_id
	 * @return: (obj)
	 */
	public function get_todo_modal_data() {
		global $wpdb;

		$task_id = (int) $_POST['task_id'];

		// If can not be done lets return YouTube Video
		if(!$this->task_helpers->todo_can_be_done((int) $_POST['task_id'])) {
			echo json_encode(array(
				'can_be_done'   => false,
				'youtube_video' => get_option('todo_youtube_id')
			));
		}
		// Else return the Task data
		else {
			$task = $wpdb->get_row($wpdb->prepare(
			"
				SELECT *
				FROM $this->table_name
				WHERE task_id = %d
			", $task_id));

			$task->content = unserialize($task->content);
			$task->is_done = $this->task_helpers->is_task_done_by_user($task_id);

			echo json_encode(array(
				'can_be_done' => true,
				'task_data'   => $task
			));
		}

		die();
	}

	/**
	 * Get if all tasks are done
	 * @param: (int) $lesson_id
	 * @return: (boolean)
	 */
	public function is_all_tasks_done($lesson_id) {
		$user_id   = get_current_user_id();
		$lesson_id = $lesson_id ? $lesson_id : (int) $_POST['lesson_id'];

		echo json_encode(array(
			'status' => $this->sensei_helpers->is_all_lessons_tasks_done($lesson_id)
		));

		die();
	}

	/**
	 * Find and ID inside and Array
	 * @param: (int) $id | (array)
	 * @return: (boolean)
	 */
	private function find_id_inside_array($id, $array) {
		$result = false;
		if($array) {
			foreach ($array as $array_item) {
				if($array_item == $id) {
					$result = true;
				}
			}
			return $result;
		}
		return false;
	}
}
