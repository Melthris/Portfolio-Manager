<?php /* Starts the session */
// I assume load is reduced when I include once here instead of static inlude of this in
// index.php like I originally did
//include_once 'src/inc/functions.php'; // or adjust the path as needed
rejectIfNotLoggedIn()
?>

<h1>Manage Projects</h1>
<div class="container2">
<div id="projectList"></div>


    <h2>Edit Project</h2>
    <div class="project-box2">
        <div class="project-container"></div>  
         <div class="project-details"> 
          <div class="project-name"><input class="textbox" type="text" id="editTitle" placeholder="Project Name"></div>
          <div class="project-date"><input class="textbox" type="date" id="editDate" placeholder="Date"></div>
         </div>
         <div class="editlinks">
         <input class="textbox" type="text" id="editLinkref" placeholder="URL of Project">
        <input class="textbox" type="text" id="editGithub" placeholder="Github of Project">
         </div>
         <div class="project-overview"><textarea id="editOverview" placeholder="Project Overview"></textarea></div>
         <h5>Tech Stack</h5>
         <div id="editTechIcons" class="tech-icon-selector"></div>
</div>
    <button onclick="updateProject()">Update</button>
    <button onclick="deleteProject()">Delete</button>
<div class="void-space"></div>

<h2>Add New Project</h2>
<div class="project-box2">
        <div class="project-container"></div>  
         <div class="project-details"> 
          <div class="project-name"><input class="textbox" type="text" id="newTitle" placeholder="Project Name"></div>
          <div class="project-date"><input class="textbox" type="date" id="newDate" placeholder="Date"></div>
         </div>
         <div class="editlinks">
         <input class="textbox" type="text" id="newLinkref" placeholder="URL of Project">
        <input class="textbox" type="text" id="newGithub" placeholder="Github of Project">
         </div>
         <div class="project-overview"><textarea id="newOverview" placeholder="Project Overview"></textarea></div>
         <h5>Tech Stack</h5>
         <div id="newTechIcons" class="tech-icon-selector"></div>
        </div>
        
<button onclick="addProject()">Add Project</button>
</div>
<div class="void-space"></div>