@extends('teacher.layouts.app')


@section('content')
   <div class="space-y-8">
       <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-green-700 via-green-600 to-emerald-600 px-6 py-8 text-white shadow-lg sm:px-10 sm:py-10">
           <div class="pointer-events-none absolute inset-0">
               <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
               <div class="absolute bottom-0 left-10 h-40 w-40 rounded-full bg-emerald-400/20 blur-2xl"></div>
           </div>


           <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
               <div class="max-w-3xl">
                   <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-900/40 px-3 py-1.5 text-xs font-medium uppercase tracking-widest text-emerald-100 backdrop-blur-md">
                       <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                       Teacher Reports
                   </div>
                   <h2 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                       Quizzes
                   </h2>
                   <p class="mt-3 max-w-2xl text-base leading-relaxed text-emerald-100">
                       Review quiz performance, participation, and publishing status across all of your quizzes.
                   </p>
               </div>


               <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                   <a href="{{ route('teacher.quizzes.index') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                           Manage Quizzes
                   </a>
               </div>
           </div>
       </div>


       <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
           <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-6 sm:px-8">
               <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                   <div>
                       <h3 class="mt-2 text-xl font-bold text-gray-900">Quiz Performance Report</h3>
                       <p class="mt-1 text-sm text-gray-500">
                           This report only includes quizzes created by your account.
                       </p>
                   </div>


                   <div class="flex items-center">
                       <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 shadow-sm">
                           Publishing and Results Overview
                       </div>
                   </div>
               </div>
           </div>


           @if ($quizzes->isEmpty())
               <div class="px-6 py-20 sm:px-8">
                   <div class="mx-auto max-w-md text-center">
                       <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                           <svg class="h-8 w-8 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                               <path d="M4.75 3A2.75 2.75 0 002 5.75v8.5A2.75 2.75 0 004.75 17h10.5A2.75 2.75 0 0018 14.25v-8.5A2.75 2.75 0 0015.25 3H4.75zm0 1.5h10.5c.69 0 1.25.56 1.25 1.25v8.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-8.5c0-.69.56-1.25 1.25-1.25z" />
                               <path d="M6.25 7.25A.75.75 0 017 6.5h6a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75zm0 3A.75.75 0 017 9.5h6a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75zm0 3A.75.75 0 017 12.5h3a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75z" />
                           </svg>
                       </div>
                       <h4 class="mt-5 text-lg font-semibold text-gray-900">No quizzes found</h4>
                       <p class="mt-2 text-sm text-gray-500">There are currently no quizzes available in this report.</p>
                   </div>
               </div>
           @else
               <div class="overflow-x-auto">
                   <table id="quizzesTable" class="min-w-full divide-y divide-emerald-100">
                       <thead class="bg-gray-50/50">
                           <tr>
                               <th onclick="sortTable('quizzesTable', 0)" class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100 sm:px-8">
                                   Quiz Title <span class="sort-icon">↕</span>
                               </th>
                               <th onclick="sortTable('quizzesTable', 1)" class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                   Classes Assigned <span class="sort-icon">↕</span>
                               </th>
                               <th onclick="sortTable('quizzesTable', 2)" class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                   Students Attempted <span class="sort-icon">↕</span>
                               </th>
                               <th onclick="sortTable('quizzesTable', 3)" class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                   Avg Score <span class="sort-icon">↕</span>
                               </th>
                               <th onclick="sortTable('quizzesTable', 4)" class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                   Status <span class="sort-icon">↕</span>
                               </th>
                               <th class="whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 sm:px-8">
                                   Actions
                               </th>
                           </tr>
                       </thead>
                       <tbody class="divide-y divide-emerald-50 bg-white">
                           @foreach ($quizzes as $quiz)
                               <tr class="transition-colors duration-150 hover:bg-emerald-50/50">
                                   <td class="px-6 py-4 sm:px-8">
                                       <div class="whitespace-nowrap text-sm font-semibold text-gray-900">
                                           {{ $quiz->title }}
                                       </div>
                                       @if ($quiz->description)
                                           <div class="mt-1 max-w-md text-sm leading-6 text-gray-500">
                                               {{ $quiz->description }}
                                           </div>
                                       @endif
                                   </td>
                                   <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                       {{ $quiz->classes_count }}
                                   </td>
                                   <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                       {{ $quiz->students_attempted_count }}
                                   </td>
                                   <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600" data-value="{{ $quiz->average_score ?? -1 }}">
                                       @if (!is_null($quiz->average_score))
                                           <span class="inline-flex px-2.5 py-1 text-xs font-semibold text-gray-600">
                                               {{ number_format($quiz->average_score, 2) }}%
                                           </span>
                                       @else
                                           N/A
                                       @endif
                                   </td>
                                   <td class="whitespace-nowrap px-6 py-4" data-value="{{ $quiz->is_published ? 'Published' : 'Unpublished' }}">
                                       @if ($quiz->is_published)
                                           <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                               Published
                                           </span>
                                       @else
                                           <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-500/20">
                                               Unpublished
                                           </span>
                                       @endif
                                   </td>
                                   <td class="whitespace-nowrap px-6 py-4 sm:px-8">
                                       <div class="flex flex-col gap-2">
                                           <a href="{{ route('teacher.reports.quiz.questions', $quiz->id) }}"
                                               class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                               View Questions
                                           </a>
                                           <a href="{{ route('teacher.reports.quiz.answers', $quiz->id) }}"
                                               class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                               View Answers
                                           </a>
                                           <a href="{{ route('teacher.analytics.quiz', $quiz->id) }}"
                                               class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-purple-700">
                                               Analytics
                                           </a>
                                       </div>
                                   </td>
                               </tr>
                           @endforeach
                       </tbody>
                   </table>
               </div>


               <script>
                   const sortState = {};


                   function sortTable(tableId, colIndex) {
                       const table = document.getElementById(tableId);
                       const tbody = table.querySelector('tbody');
                       const rows = Array.from(tbody.querySelectorAll('tr'));
                       const icons = table.querySelectorAll('.sort-icon');


                       if (!sortState[tableId]) sortState[tableId] = {};
                       const asc = !sortState[tableId][colIndex];
                       sortState[tableId][colIndex] = asc;


                       icons.forEach((icon) => {
                           icon.textContent = '↕';
                       });
                       icons[colIndex].textContent = asc ? '↑' : '↓';


                       rows.sort((a, b) => {
                           const aCell = a.querySelectorAll('td')[colIndex];
                           const bCell = b.querySelectorAll('td')[colIndex];


                           const aRaw = aCell.dataset.value !== undefined && aCell.dataset.value !== '' ? aCell.dataset.value.trim() : aCell.innerText.trim();
                           const bRaw = bCell.dataset.value !== undefined && bCell.dataset.value !== '' ? bCell.dataset.value.trim() : bCell.innerText.trim();


                           if (aRaw === '' && bRaw === '') return 0;
                           if (aRaw === '') return 1;
                           if (bRaw === '') return -1;


                           const aNum = parseFloat(aRaw);
                           const bNum = parseFloat(bRaw);


                           if (!isNaN(aNum) && !isNaN(bNum)) {
                               return asc ? aNum - bNum : bNum - aNum;
                           }


                           return asc ? aRaw.localeCompare(bRaw) : bRaw.localeCompare(aRaw);
                       });


                       rows.forEach(row => tbody.appendChild(row));
                   }
               </script>
           @endif
       </div>
   </div>
@endsection
