<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CalendrierDBNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CalendarController extends Controller
{
    public function index()
    {
        $tasks = Task::all();
        return response()->json($tasks);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'La tâche a été supprimée avec succès'], 200);
    }

    public function updateTask(Request $request, $id)
    {

        $task = Task::findOrFail($id);

        $task->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);
        return response()->json(['message' => 'Tâche mise à jour avec succès'], 200);
    }

    public function getTasks()
    {
        if (Auth::check()) {
            $userId = Auth()->user()->id;
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if ($userId) {
            $tasksCreatedByUser = Task::where('created_by', $userId)
                ->where('etat', 'accepter') 
                ->with('createdBy', 'assignedTo')
                ->get();

            $tasksAssignedToUser = Task::where('assigned_to', $userId)
                ->where('etat', 'accepter') 
                ->with('createdBy', 'assignedTo')
                ->get();

            $tasks = $tasksCreatedByUser->merge($tasksAssignedToUser);

            $tasksData = [];

            foreach ($tasks as $task) {
                $taskData = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start_time' => $task->start_time,
                    'end_time' => $task->end_time,
                    'description' => $task->description,
                    'created_by' => $task->created_by,
                    'assigned_to' => $task->assigned_to,
                    'etat' => $task->etat, 
                ];

                $tasksData[] = $taskData;
            }

            return response()->json($tasksData, 200);
        } else {
            return response()->json(['error' => 'User ID not provided'], 401);
        }
    }

    public function getTaskEnAttente()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $tasks = Task::where('assigned_to', $userId)
                ->where('etat', 'en_attente')
                ->get();

            return response()->json($tasks, 200);
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }

    public function acceptTask($id)
    {

        $task = Task::findOrFail($id);
        $task->etat = 'accepter';
        $task->save();
        return response()->json(['message' => 'Tâche acceptée avec succès'], 200);
    }

    public function deleteTask($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'La tâche n\'existe pas.'], 404);
        }

        $task->delete();

        return response()->json(['message' => 'Tâche supprimée avec succès'], 200);
    }
    public function show(Task $task)
    {
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json($task, 200);
    }

    public function getMessage($id)
    {
        $user_auth_id = 2;

        $user = User::find($id);

        if ($user) {
            if ($user_auth_id === $id) {
                return response()->json(['Message' => 'Rendez-vous avec ' . $user->name]);
            } else {
                return response()->json(['Message' => $user_auth_id], 403);
            }
        } else {
            return response()->json(['Message' => 'User not found'], 404);
        }

    }

    public function store(Request $request)
    {
        $start_time = $request->input('start_time');
        $end_time = $request->input('end_time');
        $title = $request->input('title');
        $description = $request->input('description');
        $created_by = Auth()->user()->id;
        $assigned_to = $request->input('assigned_to');
        $personneAuth = User::find($assigned_to);

        $color = $request->input('color', '#3788d8');

        $task = Task::create([
            'title' => $title,
            'description' => $description,
            'created_by' => $created_by,
            'assigned_to' => $assigned_to,
            'color' => $color,
            "start_time" => $start_time,
            "end_time" => $end_time,
        ]);

        Notification::send($personneAuth, new CalendrierDBNotify($task));

        if ($task) {
            return response()->json($task, 201);
        } else {
            return response()->json(['error' => 'An error occurred while adding the task.'], 500);
        }
    }

}
