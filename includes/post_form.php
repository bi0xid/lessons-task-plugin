<?php

/**
 * Custom ToDo forms for Lessons and Courses Pages
 * @author: Alejandro Orta (MyTinySecrets Dev)
 */
add_action( 'save_post', 'on_post_save', 10, 3 );
add_action( 'admin_menu', 'create_lesson_tasks_form' );

function create_lesson_tasks_form() {
	add_meta_box( 'lessons-task-form', 'Lesson ToDos', 'lesson_add_task_form', 'lesson', 'normal', 'high' );
	add_meta_box( 'course-task-form', 'Course Special ToDos', 'course_add_todos_form', 'course', 'normal', 'high' );
}

function on_post_save($post_id, $post, $update) {
	if($post->post_type === 'course') {

		// Custom Course MetaDatas
		$course_meta = array(
			'end_video_id'           => $_REQUEST['end_video_id'],
			'course_wistia_video_id' => $_REQUEST['course_wistia_video_id'],
			'end_course_text'        => sanitize_text_field($_REQUEST['end_course_text'])
		);

		foreach ($course_meta as $key => $value) {
			isset($value) && update_post_meta( $post_id, $key, $value );
		}

		// Special course ToDo Data
		$special_todo = array(
			'course_walkthrough_todo' => array(
				'name'  => $_REQUEST['walkthrough_todo_name'],
				'desc'  => $_REQUEST['walkthrough_todo_desc'],
				'video' => $_REQUEST['walkthrough_todo_video']
			),
			'course_comment_todo' => array(
				'name'  => $_REQUEST['comment_todo_name'],
				'desc'  => $_REQUEST['comment_todo_desc'],
				'video' => $_REQUEST['comment_todo_video']
			),
			'course_profile_todo' => array(
				'name'  => $_REQUEST['profile_todo_name'],
				'desc'  => $_REQUEST['profile_todo_desc'],
				'video' => $_REQUEST['profile_todo_video']
			)
		);

		foreach ( $special_todo as $todo_key => $array) {
			update_post_meta( $post_id, $todo_key, $array );
		};

		// Unlock course feature
		$unlock_course_date = isset($_REQUEST['unlock_time_hidden'])
			? $_REQUEST['unlock_time_hidden']
			: undefined;

		update_post_meta( $post_id, 'unlock_time', array(
			'unlock_feature' => $_REQUEST['unlock_time_feature'] === 'on',
			'unlock_date'    => $unlock_course_date
		) );
	}
	else if($post->post_type === 'lesson') {
		if(isset($_REQUEST['lesson_wistia_video_id'])) {
			update_post_meta( $post_id, 'lesson_wistia_video_id', $_REQUEST['lesson_wistia_video_id'] );
		}
	}
}

function course_add_todos_form($object, $box) {
	$walkthrough = get_post_meta($object->ID, 'course_walkthrough_todo', true);
	$comment     = get_post_meta($object->ID, 'course_comment_todo', true);
	$profile     = get_post_meta($object->ID, 'course_profile_todo', true);

	?>
		<p>
			<label>
				Here you can edit the <strong>special</strong> course ToDos, edit the description, add a <strong>Wistia Video</strong> if you want, ToDo name, etc.
			</label>

			<hr>

			<ul class="todos">
				<li>
					<strong>Walkthrough ToDo</strong>
					<label>The User need to complete the Walkthrough</label>

					<input value="<?php echo $walkthrough['name']; ?>" type="text" placeholder="Name" name="walkthrough_todo_name" id="walkthrough_todo_name">
					<input value="<?php echo $walkthrough['desc']; ?>" type="text" placeholder="Description" name="walkthrough_todo_desc" id="walkthrough_todo_desc">
					<input value="<?php echo $walkthrough['video']; ?>" type="text" placeholder="Wistia Video - optional -" name="walkthrough_todo_video" id="walkthrough_todo_video">
				</li>
				<li>
					<strong>Comment ToDo</strong>
					<label>The User need to put a comment in the current lesson</label>

					<input value="<?php echo $comment['name']; ?>" type="text" placeholder="Name" name="comment_todo_name" id="comment_todo_name">
					<input value="<?php echo $comment['desc']; ?>" type="text" placeholder="Description" name="comment_todo_desc" id="comment_todo_desc">
					<input value="<?php echo $comment['video']; ?>" type="text" placeholder="Wistia Video - optional -" name="comment_todo_video" id="comment_todo_video">
				</li>
				<li>
					<strong>Profile ToDo</strong>
					<label>The users need to fill one of the fields at their profile</label>

					<input value="<?php echo $profile['name']; ?>" type="text" placeholder="Name" name="profile_todo_name" id="profile_todo_name">
					<input value="<?php echo $profile['desc']; ?>" type="text" placeholder="Description" name="profile_todo_desc" id="profile_todo_desc">
					<input value="<?php echo $profile['video']; ?>" type="text" placeholder="Wistia Video - optional -" name="profile_todo_video" id="profile_todo_video">
				</li>
			</ul>
		</p>
	<?php
}

function lesson_add_task_form($object, $box) {
	global $wpdb;
	$table = $wpdb->prefix . 'tasks';
	$current_lesson_tasks = get_post_meta($object->ID, 'Lesson Tasks', true);

	$tasks = array();
	$new_task_meta = array();

	$quiz_todo_exist = false;
	if($current_lesson_tasks) {
		foreach ($current_lesson_tasks as $task) {
			$col = $wpdb->get_row($wpdb->prepare(
			"
				SELECT *
				FROM $table
				WHERE task_id = %s
			", $task));

			if($col->type == 2) {
				$quiz_todo_exist = true;
			}

			if(count($col) > 0) {
				$tasks[] = $col;
				$new_task_meta[] = (int) $col->task_id;
			}
		}
	}

	// En el caso de que alguno no esté en base de datos lo borramos de los meta.
	update_post_meta($object->ID, 'Lesson Tasks', $new_task_meta);
	?>

	<p>
		<input type="hidden" id="trumbowyg_svg" value="<?php echo get_stylesheet_directory(); ?>">

		<label for="second-excerpt">
			Here you can add, edit and remove this lesson taks. Remember to <strong>save any changes</strong> before leaving this page.
			First select the Type you want and then click <strong>Add a new task</strong>
		</label>
		<br /><br />

		<div class="form-actions">
			<select id="todo_type" name="todo_type">
				<option value="0">Normal</option>
				<option value="1">Images / Gif with Audio</option>
				<option value="2" <?php if($quiz_todo_exist) { ?>disabled<?php } ?>>Quiz ToDo (one per Lesson)</option>
				<option value="3">Multiple Tasks in one ToDo</option>
			</select>
			<button class="button button-large" type="button" id="add-task" data-id="<?php echo $object->ID; ?>">Add a new task</button>
		</div>

		<br />

		<ul class="task-list" data-lesson="<?php echo $object->ID; ?>">
			<?php foreach ($tasks as $key => $task) {
				global $task_tmp;
				$task_tmp = $task;

				// Normal ToDo
				if($task->type == 0) {
					include (LESSON_PLUGIN_DIR . 'template-parts/normal_todo.php');
				}
				// Images Audio ToDo
				else if($task->type == 1) {
					include (LESSON_PLUGIN_DIR . 'template-parts/images_audio_todo.php');
				}
				// Quiz ToDo
				else if($task->type == 2) {
					include (LESSON_PLUGIN_DIR . 'template-parts/quiz_todo.php');
				}
				// Multiple Tasks ToDo
				else if($task->type == 3) {
					include (LESSON_PLUGIN_DIR . 'template-parts/task_todo.php');
				}
			} ?>
		</ul>

		<input type="hidden" name="my_meta_box_nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
	</p>
	<?php
}

// Use this function to render the images based on Order
function map_images($array) {
	$result = array();
	foreach ($array as $key => $value) {
		$result[(int) $value['order']] = $value;
	}

	return $result;
}