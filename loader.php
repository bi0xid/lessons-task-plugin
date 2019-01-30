<?php
/*
Plugin Name: Lessons Tasks
Description: Adds custom ToDos to Sensei.
Version: 0.7.1
Author: Alejandro Orta (MTS Dev)
*/

define( 'LESSON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

function plugin_admin_scripts($hook) {
	if($hook == 'post.php' || $hook == 'post-new.php') {
		wp_enqueue_script('task_admin_script', plugin_dir_url( __FILE__ ).'/js/admin-script.js', false, '0.2', false);
	}
}

function plugin_admin_styles($hook) {
	if($hook == 'post.php' || $hook == 'post-new.php') {
		wp_enqueue_style('task_admin_style', plugin_dir_url( __FILE__ ).'/css/style.css', false, '0.2', false);
	}
}

add_action('admin_enqueue_scripts', 'plugin_admin_scripts');
add_action('admin_enqueue_scripts', 'plugin_admin_styles');

function new_tables_for_tasks_install() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$table = $wpdb->prefix . 'tasks';

	$sql1 = "CREATE TABLE IF NOT EXISTS $table (
			task_id int(20) NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL DEFAULT '',
			type int(20) NOT NULL,
			content longtext,
			points int(20) NOT NULL DEFAULT 0,
			PRIMARY KEY (task_id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql1);
}

register_activation_hook(__FILE__,'new_tables_for_tasks_install');

function new_tables_for_tasks_uninstall() {
	global $wpdb;

	$table = $wpdb->prefix . 'tasks';

	$sql1 = "DROP TABLE IF EXISTS $table";
	$wpdb->query($sql1);

	// Remove the custom Meta information from all the users
	foreach (get_users() as $user) {
		delete_user_meta( $user->ID, 'Done Tasks' );
		delete_user_meta( $user->ID, 'Done Lessons' );
		delete_user_meta( $user->ID, 'Opened Tasks' );
		delete_user_meta( $user->ID, 'Done Task Time' );
		delete_user_meta( $user->ID, 'special_todo_done' );
	}

	// Remove all the Meta Tasks from all the lessons
	$courses = Sensei()->course->get_all_courses();
	foreach ($courses as $course) {
		$course_lessons = Sensei()->course->course_lessons($course->ID);

		foreach ($course_lessons as $course_lesson) {
			delete_post_meta($course_lesson->ID, 'Lesson Tasks');
		}
	}
}

register_deactivation_hook(__FILE__,'new_tables_for_tasks_uninstall');

function lessons_tasks_plugin_init() {
	require dirname( __FILE__ ).'/classes/sensei_helpers.php';
	require dirname( __FILE__ ).'/includes/get_tasks.php';
	require dirname( __FILE__ ).'/includes/ajax_actions.php';
	require dirname( __FILE__ ).'/includes/post_form.php';

	$sensei_helpers = new sensei_helpers();
	$task_manager = new task_manager_class();
	$ajax_actions = new ajax_actions();
}

function action_bp_register_activity_actions( $array ) {
	// Registering our custom BP Activity
	bp_activity_set_action(
		'todos_component',
		'todo_completed',
		'Completed a ToDo',
		'bp_activity_format_activity_action_activity_update'
	);

	do_action( 'bp_activity_register_activity_actions' );
}

add_action( 'init', 'lessons_tasks_plugin_init', 100 );
add_action( 'bp_register_activity_actions', 'action_bp_register_activity_actions' );
