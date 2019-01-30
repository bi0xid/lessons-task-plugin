<?php
	$content = unserialize($task_tmp->content);
?>

<li class="todo" data-type="2" data-id="<?php echo $task_tmp->task_id; ?>">
	<h3>Quiz ToDo</h3>
	<span class="toggle-indicator" aria-hidden="true"></span>

	<div class="content" style="display:none;">
		<div class="top_form">
			<input type="text" name="task_name" placeholder="Task name" value="<?php echo $task_tmp->name; ?>"></input>
			<input type="number" name="task_points" placeholder="Task points" value="<?php echo $task_tmp->points; ?>"></input>
			<input type="text" name="quiz_id" placeholder="Quiz ID" value="<?php echo $content['quiz']; ?>"></input>
		</div>

		<div class="bottom_form">
			<div class="intro">
				<h5>Introduction</h5>
				<label>Add an introduction for the ToDo, you can use a single video or a description with an image/gif.</label>
				<div class="intro_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img class="intro_image" src="<?php echo $content['intro']['image']; ?>" alt="Introduction image">
					</div>
					<input value="<?php echo $content['intro']['title']; ?>" type="text" class="intro_title" placeholder="Intro Title">
					<input type="text" class="intro_video" placeholder="Wistia ID - Optional -" value="<?php echo $content['intro']['video']; ?>">
					<textarea name="intro_text" placeholder="Introduction text">
						<?php echo $content['intro']['desc']; ?>
					</textarea>
				</div>
			</div>

			<div class="outro_pass">
				<h5>Pass outro</h5>
				<label>Add an outro for this Quiz ToDo is the user <strong>PASS</strong> the Quiz.</label>
				<div class="outro_pass_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img class="outro_pass_image" src="<?php echo $content['outro_pass']['image']; ?>" alt="Outro image">
					</div>
					<input value="<?php echo $content['outro_pass']['title']; ?>" type="text" class="outro_pass_title" placeholder="Outro Pass Title">
					<input type="text" class="outro_pass_video" placeholder="Wistia ID - Optional -" value="<?php echo $content['outro_pass']['video']; ?>">
					<textarea name="outro_pass_text" placeholder="Introduction text">
						<?php echo $content['outro_pass']['desc']; ?>
					</textarea>
				</div>
			</div>

			<div class="outro_fail">
				<h5>Fail outro</h5>
				<label>Add an outro for this Quiz ToDo is the user <strong>FAIL</strong> the Quiz.</label>
				<div class="outro_fail_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img class="outro_fail_image" src="<?php echo $content['outro_fail']['image']; ?>" alt="Outro image">
					</div>
					<input value="<?php echo $content['outro_fail']['title']; ?>" type="text" class="outro_fail_title" placeholder="Outro Fail Title">
					<input type="text" class="outro_fail_video" placeholder="Wistia ID - Optional -" value="<?php echo $content['outro_fail']['video']; ?>">
					<textarea name="outro_fail_text" placeholder="Introduction text">
						<?php echo $content['outro_fail']['desc']; ?>
					</textarea>
				</div>
			</div>
		</div>

		<div class="actions">
			<button type="submit" class="button button-large update-task">Update</button>
			<button type="submit" class="button button-large remove-task">Remove</button>
		</div>
	</div>
</li>