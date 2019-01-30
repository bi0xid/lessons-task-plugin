<?php
	$content = unserialize($task_tmp->content);
?>

<li class="tasks" data-type="3" data-id="<?php echo $task_tmp->task_id; ?>">
	<h3>Multiple Tasks ToDo</h3>
	<span class="toggle-indicator" aria-hidden="true"></span>

	<div class="content" style="display:none;">
		<div class="top_form">
			<input type="text" name="task_name" placeholder="Task name" value="<?php echo $task_tmp->name; ?>"></input>
			<input type="number" name="task_points" placeholder="Task points" value="<?php echo $task_tmp->points; ?>"></input>
		</div>

		<div class="bottom_form">
			<div class="intro">
				<h5>Introduction</h5>
				<label>Add an introduction for the ToDo, you can use a single video or a description with an image/gif.</label>
				<div class="intro_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img src="<?php echo $content['intro']['image']; ?>" class="intro_image" src="" alt="Introduction image">
					</div>
					<input value="<?php echo $content['intro']['title']; ?>" type="text" class="intro_title" placeholder="Intro Title">
					<input value="<?php echo $content['intro']['video']; ?>" type="text" class="intro_video" placeholder="Wistia ID - Optional -">
					<textarea name="intro_text" placeholder="Introduction text">
						<?php echo $content['intro']['desc']; ?>
					</textarea>
				</div>
			</div>

			<div class="content">
				<h5>Main Content</h5>
				<label>Here you can add all the items you want, each item can be <strong>one wistia video</strong> or <strong>an image followed by a description/audio</strong>. The user will move to the next area when clicking a button.</label>
				<button type="button" class="add_item button button-large">
					Add new Item
				</button>
				<ul class="todo_tasks">
					<?php
						if(count($content['content']) > 0) {
							foreach ($content['content'] as $task_item) {
					?>
						<li data-order="<?php echo $task_item['order']; ?>">
							<input type="text" class="task_video" placeholder="Task Wistia Video ID" value="<?php echo $task_item['video']; ?>">
							<input type="text" class="task_title" placeholder="Task Title" value="<?php echo $task_item['title']; ?>">
							<input type="text" class="task_desc" placeholder="Task Description" value="<?php echo $task_item['desc']; ?>">

							<div class="left-inputs">
								<div class="image">
									<button class="image_item">Change Image</button>
									<div src="<?php echo $task_item['image']; ?>" style="background-image:url(<?php echo $task_item['image']; ?>)" class="img_wrapper selected_image"></div>
								</div>

								<div class="audio">
									<button class="add_audio">Change Audio</button>
									<div class="audio_wrapper">
										<audio class="selected_audio" controls>
											<source src="<?php echo $task_item['audio']; ?>">
										</audio>
									</div>
								</div>
							</div>

							<div class="right-actions">
								<a href="#" class="order">Change Order</a>
								<a href="#" class="remove">Remove Item</a>
							</div>
						</li>
					<?php
							};
						};
					?>
				</ul>
			</div>

			<div class="outro">
				<h5>Outro</h5>
				<label>Add an outro for the ToDo.</label>
				<div class="outro_content">
					<div class="image">
						<button class="button button-large change-media">Change Image</button>
						<img src="<?php echo $content['outro']['image']; ?>" class="outro_image" src="" alt="Outro image">
					</div>
					<input value="<?php echo $content['outro']['title']; ?>" type="text" class="outro_title" placeholder="Outro Title">
					<input value="<?php echo $content['outro']['video']; ?>" type="text" class="outro_video" placeholder="Wistia ID - Optional -">
					<textarea name="outro_text" placeholder="Introduction text">
						<?php echo $content['outro']['desc']; ?>
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