<?php
/**
 * MTS LoveSchool Helpers
 * This is a copy of the file located inside social-learner theme
 *
 * @author: Alejandro Orta
 */

class sensei_helpers {
	/**
	 * Get the user avatar.
	 * @param: Int user ID - or no for the current user
	 * @return: String with the image link
	 */
	public function get_user_avatar($user_id = null) {
		$id = $user_id ? $user_id : get_current_user_id();

		$user_avatar = get_usermeta($id, 'avatar', true);

		if($user_avatar) {
			$avatar_src = strlen($user_avatar['path']) == 0
				? "//www.gravatar.com/avatar/0b6978a4245d3b26124b2f4411ff8289?s=50&r=g&d=mm"
				: $user_avatar['path'];
		}
		else {
			$avatar_src = "//www.gravatar.com/avatar/0b6978a4245d3b26124b2f4411ff8289?s=50&r=g&d=mm";
		}

		return $avatar_src;
	}

	/**
	 * Get current course ID.
	 * Even if the user is inside a lesson page
	 * @return int
	 */
	public function get_current_course_id() {
		global $woothemes_sensei;

		$group_status = groups_get_groupmeta(bp_get_group_id(), 'bp_course_attached', true);
		$post = get_post((int) $group_status);

		$current_course_id = count(Sensei()->course->course_lessons($post->ID)) > 0
			? $post->ID
			: absint(get_post_meta($post->ID, '_lesson_course', true));

		return $current_course_id;
	}

	/**
	 * Parse Course Unlock Date
	 * @param: Date string
	 * @return: String with the correct date
	*/
	public function parse_unlock_course_date($date) {
		$parsed_date = date_parse($date);

		$month = $parsed_date['month'] < 10
			? '0'.$parsed_date['month']
			: $parsed_date['month'];

		$day = $parsed_date['day'] < 10
			? '0'.$parsed_date['day']
			: $parsed_date['day'];

		$hour = $parsed_date['hour'] < 10
			? '0'.$parsed_date['hour']
			: $parsed_date['hour'];

		$result = $parsed_date['year'].'-'.$month.'-'.$day.'-'.$hour;

		return $result;
	}

	/**
	 * Get current lesson ID.
	 * @return int
	 */
	public function get_current_lesson_id() {
		global $woothemes_sensei;

		$group_status = groups_get_groupmeta(bp_get_group_id(), 'bp_course_attached', true);
		$post = get_post((int) $group_status);

		return $post->ID;
	}

	/**
	 * Is lesson in progress
	 * If the lesson is not done and have any prerequisite
	 * and that prerequisite is not done then the lesson is 
	 * in progress.
	 * If there is only one lesson we check the modules progress.
	 * @param: (int) $lesson_id
	 * @param: (int) - optional - $course_id
	 * @return: (bool)
	 */
	public function is_lesson_in_progress($lesson_id, $course_id = null) {
		global $woothemes_sensei, $current_user;

		$current_course_id = $course_id
			? $course_id
			: $this->get_current_course_id();

		$lesson_is_done = WooThemes_Sensei_Utils::user_completed_lesson($lesson_id);

		// Get lesson module status
		$lesson_module = $this->get_lesson_module($lesson_id, $current_course_id);

		$module_progress = $woothemes_sensei->modules->get_user_module_progress($lesson_module->term_id, $current_course_id, $current_user->ID);
		$is_module_in_progress = $module_progress > 0 && $module_progress < 100
			? true
			: false;

		$is_first_course_module = $this->get_lesson_module_order($lesson_id, $current_course_id)['module'] == 1;

		if($lesson_is_done) {
			return false;
		}
		// Is the lesson is the first lesson of the module and the module is in progress
		else if($this->is_first_module_lesson($lesson_id, $lesson_module->term_id) && $is_module_in_progress) {
			return true;
		}
		else if($is_first_course_module && $this->is_first_module_lesson($lesson_id, $lesson_module->term_id)) {
			return true;
		}
		// Check if the lesson is not done but his prerequisite is
		else if(!$lesson_is_done) {
			$lesson_prerequisite = get_post_meta($lesson_id, '_lesson_prerequisite', true);
			if(!$lesson_prerequisite && $is_module_in_progress) {
				return true;
			}
			$lesson_prerequisite_is_complete = $lesson_prerequisite
				? WooThemes_Sensei_Utils::user_completed_lesson((int) $lesson_prerequisite)
				: false;

			return $lesson_prerequisite_is_complete;
		}
		// Else returns false
		else {
			return false;
		}
	}

	/**
	 * Get lesson module
	 * @param: (int) $lesson_id
	 * @param: (int) - optional - $course_id
	 * @return: Bool if no module found. Obj if there is a module for the lesson
	 */
	public function get_lesson_module($lesson_id, $course_id = null) {
		global $woothemes_sensei;

		if(!$course_id) {
			$course_id = $this->get_current_course_id();
		}

		$modules = $woothemes_sensei->modules->get_course_modules($course_id);
		$result = null;
		foreach ($modules as $module) {
			$lessons = $woothemes_sensei->modules->get_lessons($course_id, $module->term_id);
			foreach ($lessons as $lesson) {
				if($lesson->ID === $lesson_id) {
					$result = $module;
				}
			}
		}

		return $result;
	}

	/**
	 * Get the lesson and the module order inside the course
	 * @param: (int) $lesson_id
	 * @param: (int) - optional - $course_id
	 * @return: (int) Module order
	 */
	public function get_lesson_module_order($lesson_id, $course_id =  null) {
		global $woothemes_sensei;

		if(!$course_id) {
			$course_id = $this->get_current_course_id();
		}

		$modules = $woothemes_sensei->modules->get_course_modules($course_id);

		$result = array(
			'module' => 0,
			'lesson' => 0
		);

		foreach ($modules as $key_module => $module) {
			$lessons = $woothemes_sensei->modules->get_lessons($course_id, $module->term_id);

			foreach ($lessons as $key_lesson => $lesson) {
				if($lesson->ID === $lesson_id) {
					$result['module'] = ++$key_module;
					$result['lesson'] = ++$key_lesson;
				}
			}
		}

		return $result;
	}

	/**
	 * Get the lesson order inside the course
	 * @param: (int) $lesson_id
	 * @return: (int) / (bool)
	 */
	public function get_lesson_order($lesson_id) {
		$course_id = $this->get_current_course_id();
		$lessons = Sensei()->course->course_lessons($course_id);

		$result = null;
		foreach ($lessons as $key => $lesson) {
			if($lesson->ID == $lesson_id) {
				$result = --$key;
			}
		}

		return $result;
	}

	/**
	 * This functions returns a boolean that show if the lesson is the first lesson
	 * of the current course.
	 * @param: (int) $lesson_id
	 * @return: (bool)
	 */
	public function is_first_course_lesson($lesson_id, $course_id_param = null) {
		$course_id = $course_id_param
			? $course_id_param
			: $this->get_current_course_id();

		$result = false;
		foreach (Sensei()->course->course_lessons($course_id) as $lesson) {
			// La primera lección no tiene requisitos, será nuestro comprobante
			if( !get_post_meta( $lesson->ID, '_lesson_prerequisite', true ) ) {
				// Si dicha lección es igual a la que enviamos entonces estamos ante la primera lección.
				if($lesson->ID == $lesson_id) {
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Get first course's lesson
	 * @param: (int) $course_id
	 * @return: (int) $lesson_id
	 */
	public function get_first_course_lesson($course_id) {
		$result = null;
		foreach (Sensei()->course->course_lessons($course_id) as $lesson) {
			if( !get_post_meta( $lesson->ID, '_lesson_prerequisite', true ) ) {
				$result = $lesson->ID;
			}
		}
		return $result;
	}

	/**
	 * This functions returns a boolean that show if the lesson is the first lesson
	 * of his module.
	 * @param: (int) $lesson_id | (int) $module_id
	 * @return: (bool)
	 */
	private function is_first_module_lesson($lesson_id, $module_id = null) {
		global $woothemes_sensei;

		if(!$module_id) {
			$module_id = $this->get_lesson_module($lesson_id)->term_id;
		}

		$current_course_id = $this->get_current_course_id();
		$lessons = $woothemes_sensei->modules->get_lessons($current_course_id, $module_id);

		$first_module_lesson_id = array_shift($lessons)->ID;

		if($first_module_lesson_id == $lesson_id) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the percentage of the course
	 * @param: (int) $course_id | (int) $user_id
	 * @return: (float) Percentage
	 */
	public function get_course_progress_percentage($course_id, $user_id) {
		global $woothemes_sensei;

		$lessons_completed = 0;
		$course_lessons    = $woothemes_sensei->post_types->course->course_lessons($course_id);
		$lesson_count      = count($course_lessons);

		foreach ($course_lessons as $lesson) {
			if (WooThemes_Sensei_Utils::user_completed_lesson($lesson->ID, $user_id)) {
				++$lessons_completed;
			}
		}

		return abs(round((doubleval($lessons_completed) * 100) / ($lesson_count), 0));
	}

	/**
	 * Given a Date gets days ago
	 * @param: (string) $date
	 * @return: (string) Days ago
	 */
	public function get_hours_ago($date) {
		$date_time = new DateTime($date);
		$now       = new DateTime();
		$interval  = $date_time->diff($now);

		if($interval->h > 1) {
			return $interval->format('%h hours').' ago';
		}
		else {
			return 'few moments ago';
		}
	}

	/**
	 * Get all active/completed courses of the actual user
	 * @return: (array) courses ids
	 */
	public function get_user_courses() {
		global $woothemes_sensei;

		$status_query = array( 'user_id' => get_current_user_id(), 'type' => 'sensei_course_status' );
		$user_courses_logs = WooThemes_Sensei_Utils::sensei_check_for_activity( $status_query , true );

		if ( !is_array($user_courses_logs) ) {
			$user_courses_logs = array( $user_courses_logs );
		}

		$completed_ids = $active_ids = array();
		foreach( $user_courses_logs as $course_status ) {
			if ( WooThemes_Sensei_Utils::user_completed_course( $course_status, get_current_user_id() ) ) {
				$completed_ids[] = (int) $course_status->comment_post_ID;
			}
			else {
				$active_ids[] = (int) $course_status->comment_post_ID;
			}
		}

		return array_merge($completed_ids, $active_ids);
	}

	/**
	 * Get if all lesson tasks are done
	 * @param: (int) $lesson_id
	 * @return: Boolean
	 */
	public function is_all_lessons_tasks_done($lesson_id) {
		$lesson_tasks    = get_post_meta( $lesson_id, 'Lesson Tasks', true );
		$user_done_tasks = get_user_meta( get_current_user_id(), 'Done Tasks', true );

		// Lets check the normal ToDos
		$count = 0;
		$normal_todos_are_done = false;

		if(gettype($lesson_tasks) === 'array' && gettype($user_done_tasks) === 'array') {
			foreach ($lesson_tasks as $lesson_task) {
				if(($key = array_search($lesson_task, $user_done_tasks)) !== false) {
					$count++;
				}
			}
			if($count == count($lesson_tasks)) {
				$normal_todos_are_done = true;
			}
		}

		// Is the first lesson? lets check Special ToDos
		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		if($this->is_first_course_lesson($lesson_id, $course_id)) {
			if($this->are_special_todos_done($course_id) && $normal_todos_are_done) {
				return true;
			}
			else {
				return false;
			}
		}
		// Else lets return normal todos status
		else {
			return $normal_todos_are_done;
		}
	}

	public function are_special_todos_done($course_id) {
		$user_special_todos_done = get_user_meta( get_current_user_id(), 'special_todo_done', true )[$course_id];

		// Como solo guardamos los todos ya hechos solo tenemos que comprobar la longitud del array
		// 3 significará que todos los Special ToDos ya están hecho
		return count($user_special_todos_done) === 3;
	}
}