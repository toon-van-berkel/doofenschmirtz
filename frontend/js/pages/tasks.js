import { tasksApi } from '../api/index.js';

// TODO [TASK LIST]: Use tasksApi.list() for GET /api/tasks and render loading, empty, error, and open-task states.
// Each task should eventually show title, description/criteria summary, approved reward, location, and task image metadata,
// with an action that loads tasksApi.view(taskId). Authentication must be active for the page.
// TODO [TASK CREATION]: Add an authenticated form using tasksApi.create() / POST /api/tasks/create.
// The server owns pending status and reward approval; the user must not choose points. Future task images require FormData,
// server MIME/size validation, random filenames, and paths stored in task_images rather than image BLOBs.
void tasksApi;
