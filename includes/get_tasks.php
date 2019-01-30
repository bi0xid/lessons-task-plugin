<?php

class task_manager_class {

	private $task_helpers;
	private $sensei_helpers;

	public function __construct() {
		require_once plugin_dir_path( __FILE__ ).'../classes/task_helpers.php';

		$this->task_helpers   = new task_helpers();
		$this->sensei_helpers = new sensei_helpers();

		add_action( 'get_lesson_tasks_done', array( $this, 'get_lesson_tasks_done' ) );
		add_action( 'get_course_tasks_done', array( $this, 'get_course_tasks_done' ) );
		add_action( 'get_course_special_todos', array( $this, 'get_course_special_todos' ) );

		add_action( 'pending_lessons_tasks', array( $this, 'get_lesson_task' ) );
		add_action( 'pending_course_tasks', array( $this, 'get_all_course_pending_tasks' ) );
	}

	public function get_course_special_todos() {
		$course_id = $this->sensei_helpers->get_current_course_id();

		// Get special course ToDos inside one single array.
		$todos = array(
			'walkthrough_todo' => get_post_meta( $course_id, 'course_walkthrough_todo', true ),
			'comment_todo'     => get_post_meta( $course_id, 'course_comment_todo', true ),
			'profile_todo'     => get_post_meta( $course_id, 'course_profile_todo', true )
		);

		$user_todos_done = get_user_meta( get_current_user_id(), 'special_todo_done', true )[$course_id];

		foreach ($todos as $key => $value) {
			$is_done = $user_todos_done
				? $user_todos_done[$key] ? 'done' : ''
				: NULL;

			echo '<a href="#" class="task-item special-todo '.$is_done.'" data-course="'.$course_id.'" data-id="'.$key.'" data-name="'.$value['name'].'" data-desc="'.$value['desc'].'" data-video="'.$value['video'].'"><span class="icon"><i class="fa fa-check" aria-hidden="true"></i></span><span>'.$value['name'].'</span></a>';
		}
	}

	public function get_course_tasks_done() {
		$user_id   = get_current_user_id();
		$course_id = $this->sensei_helpers->get_current_course_id();

		$user_done    = 0;
		$course_todos = 3;

		$lessons = Sensei()->course->course_lessons($course_id);
		$user_done_tasks = get_user_meta($user_id, 'Done Tasks')[0];

		foreach ($lessons as $lesson) {
			$lesson_tasks = get_post_meta($lesson->ID, 'Lesson Tasks', true);

			$is_lesson_in_progress = $this->sensei_helpers->is_lesson_in_progress($lesson->ID);
			$is_lesson_done = WooThemes_Sensei_Utils::user_completed_lesson($lesson->ID);

			if($is_lesson_in_progress || $is_lesson_done) {
				$course_todos += count($lesson_tasks);

				foreach ($lesson_tasks as $lesson_task) {
					$user_task_key = $user_done_tasks
						? array_search($lesson_task, $user_done_tasks)
						: NULL;

					if($user_task_key !== false && $user_task_key !== NULL) {
						$user_done += 1;
					}
				}
			}
		}

		// Special ToDos Counter
		$special_todos = get_user_meta( get_current_user_id(), 'special_todo_done', true )[$course_id];
		if($special_todos) {
			$user_done += count($special_todos);
		}

		echo '<div class="todo-count"><span class="content"><span class="d">'.$user_done.'</span> | <span class="p">'.$course_todos.'</span> To-Do`s Completed</span></div>';
	}

	public function get_lesson_tasks_done() {
		$lesson_id = $this->sensei_helpers->get_current_lesson_id();
		$course_id = $this->sensei_helpers->get_current_course_id();

		$user_id    = get_current_user_id();
		$done_tasks = get_user_meta($user_id, 'Done Tasks', true);
		$tasks      = get_post_meta($lesson_id, 'Lesson Tasks', true);

		$user_done   = 0;
		$total_tasks = count($tasks);

		if($done_tasks) {
			foreach ($done_tasks as $user_done_task) {
				foreach ($tasks as $task) {
					if($user_done_task === $task) {
						$user_done += 1;
					}
				}
			}
		}

		// Special ToDos Counter for the first lesson only
		if($this->sensei_helpers->get_lesson_module_order($lesson_id)['lesson'] == 1) {
			$total_tasks += 3;
			$special_todos = get_user_meta( get_current_user_id(), 'special_todo_done', true )[$course_id];
			if($special_todos) {
				$user_done += count($special_todos);
			}
		}

		echo '<div class="todo-count"><span class="content"><span class="d">'.$user_done.'</span> | <span class="t">'.$total_tasks.'</span> To-Do`s Completed</span></div>';
	}

	public function get_lesson_task() {
		$lesson_id   = $this->sensei_helpers->get_current_lesson_id();
		$lesson_name = get_the_title($lesson_id);

		$tasks       = get_post_meta($lesson_id, 'Lesson Tasks', true);
		$task_data   = $this->get_tasks_data($tasks);
		$tasks_array = [];

		// If some ToDo is done we will set this to false
		$confetti = true;

		foreach ($task_data as $task) {
			$task_lesson = $this->task_helpers->get_lesson_from_task($task->task_id);

			$is_task_done   = $this->task_helpers->is_task_done_by_user($task->task_id);
			$is_done_class  = $is_task_done ? 'done' : '';

			// Confetti will be only for not done tasks and not for the quiz todo
			if($is_task_done || $task->type == 2) {
				$confetti = false;
			}

			$element = array(
				'html' => array(
					'data-lessonid' => $lesson_id,
					'data-type'     => $task->type,
					'data-lesson'   => $lesson_name,
					'data-done'     => $is_task_done,
					'data-id'       => $task->task_id,
					'class'         => 'task-item '.$is_done_class,
				),
				'name' => $task->name
			);

			// Not done tasks will go to the top
			if($is_task_done) {
				array_push($tasks_array, $element);
			}
			else {
				array_unshift($tasks_array, $element);
			}
		}

		$this->print_tasks($tasks_array, $confetti);
	}

	public function get_all_course_pending_tasks() {
		global $wootemes_sensei;

		$course_id = $this->sensei_helpers->get_current_course_id();
		$lessons = Sensei()->course->course_lessons($course_id);

		// Obtenemos las ID's de las task
		$result_ids = array();

		foreach ($lessons as $lesson) {
			$lesson_task = get_post_meta($lesson->ID, 'Lesson Tasks', true);

			$is_lesson_in_progress = $this->sensei_helpers->is_lesson_in_progress($lesson->ID);
			$is_lesson_done = WooThemes_Sensei_Utils::user_completed_lesson($lesson->ID);

			if(gettype($lesson_task) == 'array' && ($is_lesson_in_progress || $is_lesson_done)) {
				$result_ids = array_merge($lesson_task, $result_ids);
			}
		}

		$task_data   = $this->get_tasks_data($result_ids);
		$tasks_array = [];

		// If some ToDo is done we will set this to false
		$confetti = true;

		foreach ($task_data as $task) {
			$task_lesson = $this->task_helpers->get_lesson_from_task($task->task_id, $course_id);
			$lesson_name = get_the_title($task_lesson->ID);

			$is_task_done   = $this->task_helpers->is_task_done_by_user($task->task_id);
			$is_done_class  = $is_task_done ? 'done' : '';

			if($is_task_done) {
				$confetti = false;
			}

			$element = array(
				'html' => array(
					'data-id'       => $task->task_id,
					'data-lessonid' => $task_lesson->ID,
					'data-type'     => $task->type,
					'data-done'     => $is_task_done,
					'data-lesson'   => $lesson_name,
					'class'         => 'task-item '.$is_done_class,
				),
				'name' => $task->name
			);

			if($is_task_done) {
				array_push($tasks_array, $element);
			}
			else {
				array_unshift($tasks_array, $element);
			}
		}

		$this->print_tasks($tasks_array, $confetti);
	}

	private function get_tasks_data($tasks_ids) {
		global $wpdb;
		$table = $wpdb->prefix . 'tasks';

		$result = array();
		foreach ($tasks_ids as $task_id) {
			$result[] = $wpdb->get_row($wpdb->prepare(
			"
				SELECT *
				FROM $table
				WHERE task_id = %d
			", $task_id));
		}

		return $result;
	}

	private function print_tasks($array, $confetti) {
		$confetti && $array[0]['html']['data-confetti'] = true;

		foreach ($array as $item) {
			echo '<a href="#" ';
			foreach ($item['html'] as $key => $value) {
				echo $key.'="'.$value.'" ';
			}
			echo '><span class="icon"><i class="fa fa-check" aria-hidden="true"></i></span><span>'.$item['name'].'</span></a>';
		}
	}
}
