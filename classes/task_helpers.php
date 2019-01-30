<?php

class task_helpers {

	private $sensei_helpers;

	public function __construct() {
		$this->sensei_helpers = new sensei_helpers();
	}

	/**
	 * Get Lesson from a Task id
	 *
	 * @param: (int) $task_id | (int) $course_id - optional
	 * @return: (obj) Lesson object
	 */
	public function get_lesson_from_task($task_id, $course_id = null) {
		global $wootemes_sensei;

		$course_id = $course_id = null
			? $this->sensei_helpers->get_current_course_id()
			: (int) $course_id;
		$lessons = Sensei()->course->course_lessons($course_id);

		$result = null;
		foreach ($lessons as $lesson) {
			foreach (get_post_meta($lesson->ID, 'Lesson Tasks', true) as $task) {
				if($task == $task_id) {
					$result = $lesson;
				}
			}
		}

		return $result;
	}

	/**
	 * Get last task id
	 *
	 * @return: (int) Task id
	 */
	public function get_last_task_id() {
		global $wpdb;

		$table = $wpdb->prefix . 'tasks';

		$result = $wpdb->get_col($wpdb->prepare(
		"
			SELECT task_id
			FROM $table
			ORDER BY task_id DESC LIMIT 0 , 1
		", $table));

		return (int) $result[0] ? (int) $result[0] : 0;
	}

	/**
	 * Clear user opened task
	 * We use this function to remove all removed task from the user meta
	 * @param: (int) $user_id
	 */
	public function clear_user_meta_tasks($user_id) {
		global $wpdb;

		$table = $wpdb->prefix . 'tasks';

		$user_opened_tasks = get_user_meta($user_id, 'Opened Tasks', true);
		$user_done_tasks   = get_user_meta($user_id, 'Done Tasks', true);

		$result_opened = array();

		if($user_opened_task) {
			foreach ($user_opened_tasks as $user_opened_task) {
				$col = $wpdb->get_row($wpdb->prepare(
				"
					SELECT *
					FROM $table
					WHERE task_id = %s
				", $user_opened_task));

				if(count($col) > 0) {
					$result_opened[] = (int) $col->task_id;
				}
			}
		}

		$result_done = array();
		if($user_done_task) {
			foreach ($user_done_tasks as $user_done_task) {
				$col = $wpdb->get_row($wpdb->prepare(
				"
					SELECT *
					FROM $table
					WHERE task_id = %s
				", $user_done_task));

				if(count($col) > 0) {
					$result_done[] = (int) $col->task_id;
				}
			}			
		}

		update_user_meta($user_id, 'Opened Tasks', $result_opened);
		update_user_meta($user_id, 'Done Tasks', $result_done);
	}

	/**
	 * Get if task is opened by user
	 * @param: (int) $task_id
	 */
	public function is_task_opened_by_user($task_id) {
		$user_opened_tasks = get_user_meta(get_current_user_id(), 'Opened Tasks', true);
		$key = $user_opened_tasks ? array_search($task_id, $user_opened_tasks) : NULL;
		return $key !== false && $key !== NULL;
	}

	/**
	 * Get if task is Done by user
	 * @param: (int) $task_id
	 */
	public function is_task_done_by_user($task_id) {
		$user_done_tasks = get_user_meta(get_current_user_id(), 'Done Tasks', true);
		$key = $user_done_tasks ? array_search($task_id, $user_done_tasks) : NULL;
		return $key !== false && $key !== NULL;
	}

	/**
	 * Check the current lesson status for a user
	 * If all ToDos are done the lesson is set as completed
	 * @param: (int) $lesson_id
	 * @return: (bool) Will return if the lesson is completed or not
	 */
	public function check_user_lesson_status($lesson_id) {
		$user_done_tasks = get_user_meta( get_current_user_id(), 'Done Tasks', true );
		$lesson_tasks = get_post_meta( $lesson_id, 'Lesson Tasks', true );

		$count = 0;
		foreach ($lesson_tasks as $lesson_task) {
			if(($key = array_search($lesson_task, $user_done_tasks)) !== false) {
				$count++;
			}
		}
		if($count == count($lesson_tasks)) {
			$this->add_lesson_done_to_user( get_current_user_id(), $lesson_id );
			return true;
		}

		return false;
	}

	/**
	 * Check the current lesson status for a user
	 * If all ToDos are done the lesson is set as completed
	 * @param: (int) $user_id | (int) $lesson_id
	 * @return: (array) Current completed lessons by the user
	 */
	public function add_lesson_done_to_user($user_id, $lesson_id) {
		if(!get_user_meta($user_id, 'Done Lessons')) {
			add_user_meta($user_id, 'Done Lessons', array(), false);
		}

		$current_user_done_lessons = get_user_meta($user_id, 'Done Lessons')[0];

		if(($key = array_search($lesson_id, $current_user_done_lessons)) === false) {
			$current_user_done_lessons[] = $lesson_id;
		}

		update_user_meta($user_id, 'Done Lessons', $current_user_done_lessons);
	}

	/**
	 * Get Task progress (done/pending) for a lesson
	 * @param: (int) $lesson_id
	 * @return: object
	 */
	public function get_lesson_task_progress($lesson_id, $course_id = null) {
		$user_id = get_current_user_id();

		$user_done_tasks = get_user_meta($user_id, 'Done Tasks', true);
		$lesson_tasks    = get_post_meta($lesson_id, 'Lesson Tasks', true);

		$done_tasks = 0;
		$total      = count($lesson_tasks);

		if($user_done_tasks) {	
			foreach ($lesson_tasks as $lesson_task) {
				if(($key = array_search($lesson_task, $user_done_tasks)) !== false) {
					$done_tasks++;
				}
			}
		}

		// Lets get the Specials ToDo is Lesson is the first one
		if($course_id) {
			if($this->sensei_helpers->is_first_course_lesson($lesson_id, $course_id)) {
				$user_specials_todos = get_user_meta( $user_id, 'special_todo_done', true )[$course_id];

				$total += 3;
				$done_tasks += count($user_specials_todos);
			}
		}

		return array(
			'total' => $total,
			'done'  => $done_tasks
		);
	}

	/**
	 * Get Task points from data base
	 * @param: (INT) $task_id
	 * @return: INT
	 */
	public function get_task_points($task_id) {
		global $wpdb;
		$table = $wpdb->prefix . 'tasks';

		$task = $wpdb->get_row($wpdb->prepare(
		"
			SELECT *
			FROM $table
			WHERE task_id = %d
		", $task_id));

		return $task->points;
	}

	/**
	 * Get Task name from data base
	 * @param: (INT) $task_id
	 * @return: STRING
	 */
	public function get_task_name($task_id) {
		global $wpdb;
		$table = $wpdb->prefix . 'tasks';

		$task = $wpdb->get_row($wpdb->prepare(
		"
			SELECT *
			FROM $table
			WHERE task_id = %d
		", $task_id));

		return $task->name;
	}

	/**
	 * Get Task Data drom data base
	 * @param: (int) $task_id
	 * @return: (bool) Returns true if the las ToDo was done 5 minues ago
	 */
	public function todo_can_be_done($task_id) {
		if($this->is_task_done_by_user($task_id)) {
			return true;
		}
		else {
			$now = date('d-m-Y H:i', time());
			$wait_time = (int) get_option('todo_wait_time');

			$user_todo_times = get_user_meta(get_current_user_id(), 'Done Task Time', true);
			$last_todo_done_time = $user_todo_times
				? end($user_todo_times)['time']
				: false;

			if($last_todo_done_time) {
				return round(abs(strtotime($last_todo_done_time) - strtotime($now)) / 60,2) >= $wait_time;
			}

			return true;
		}
	}

	/**
	 * Get if the task is the first Task of the lesson
	 * @param: (int) $task_id
	 * @return: (bool)
	 */
	public function if_first_task_of_lesson($lesson_id, $task_id) {
		$lesson_taks = get_post_meta($lesson_id, 'Lesson Tasks', true);
		return array_shift(array_values($lesson_taks)) == $task_id;
	}
}