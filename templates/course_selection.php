<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPrep - Home</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B700%3B900&family=Noto+Sans%3Awght%40400%3B500%3B700%3B900">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>
<body style='font-family: Inter, "Noto Sans", sans-serif;'>
    <div class="relative flex size-full min-h-screen flex-col bg-slate-50 group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-b-slate-200 px-4 sm:px-10 py-3 bg-white">
                <div class="flex items-center gap-4 sm:gap-8">
                    <a href="index.php" class="flex items-center gap-3 text-slate-900">
                        <div class="size-5 text-green-600">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path></svg>
                        </div>
                        <h2 class="text-slate-900 text-lg font-bold leading-tight tracking-[-0.015em]">ExamPrep</h2>
                    </a>
                    <div class="hidden sm:flex items-center gap-8">
                        <a class="text-slate-900 text-sm font-medium leading-normal" href="index.php">My Exams</a>
                        <a class="text-slate-900 text-sm font-medium leading-normal" href="index.php?page=study_plans">Study Plans</a>
                        <a class="text-slate-900 text-sm font-medium leading-normal" href="#">Resources</a>
                    </div>
                </div>
                <div class="flex flex-1 justify-end items-center gap-2 sm:gap-4">
                    <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 text-slate-900">
                        <div class="size-5"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M221.8,175.94C216.25,166.38,208,139.33,208,104a80,80,0,1,0-160,0c0,35.34-8.26,62.38-13.81,71.94A16,16,0,0,0,48,200H88.81a40,40,0,0,0,78.38,0H208a16,16,0,0,0,13.8-24.06ZM128,216a24,24,0,0,1-22.62-16h45.24A24,24,0,0,1,128,216ZM48,184c7.7-13.24,16-43.92,16-80a64,64,0,1,1,128,0c0,36.05,8.28,66.73,16,80Z"></path></svg></div>
                    </button>
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("https://source.unsplash.com/random/100x100/?portrait");'></div>
                </div>
            </header>
            
            <main id="layout-content-container" class="flex justify-center flex-1 px-4 sm:px-10 py-5">
                <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
                    <div id="main-content-area">
                        <div class="w-full mb-8">
                            <h1 class="text-slate-900 text-3xl font-bold tracking-tight mb-4">Find Your Next Challenge</h1>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></svg>
                                </div>
                                <input type="text" id="course-search-input" onkeyup="filterCourses()" placeholder="Search for a course or exam..." class="block w-full rounded-lg border-slate-300 bg-white p-4 pl-10 text-base shadow-sm focus:border-green-500 focus:ring-green-500"/>
                            </div>
                        </div>
                        <h2 class="text-slate-900 text-2xl font-bold tracking-tight px-4 pb-3">All Courses</h2>
                        <div id="course-grid-container" class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5 p-4">
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="course-card-item flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 text-left cursor-pointer transition-all duration-300 ease-in-out hover:shadow-xl hover:border-green-500 hover:-translate-y-1" onclick="selectCourse('<?php echo htmlspecialchars($subject['course']); ?>')">
                                        <div class="text-white bg-green-600 p-3 rounded-lg w-fit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" fill="currentColor" viewBox="0 0 256 256"><path d="M224,48H160a40,40,0,0,0-32,16A40,40,0,0,0,96,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H96a24,24,0,0,1,24,24,8,8,0,0,0,16,0,24,24,0,0,1,24-24h64a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48ZM96,192H32V64H96a24,24,0,0,1,24,24V200A39.81,39.81,0,0,0,96,192Zm128,0H160a39.81,39.81,0,0,0-24,8V88a24,24,0,0,1,24-24h64Z"></path></svg>
                                        </div>
                                        <div class="flex-grow">
                                            <h3 class="course-title text-slate-800 text-lg font-bold leading-tight"><?php echo htmlspecialchars($subject['course']); ?></h3>
                                            <p class="text-sm text-slate-500 mt-1">Prepare for your certification.</p>
                                        </div>
                                        <span class="text-xs font-semibold text-green-800 py-1 px-3 bg-green-100 rounded-full w-fit"><?php echo $subject['exam_count']; ?> Exams Available</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p id="no-courses-message" class="text-slate-500 col-span-full text-center py-10">No courses found.</p>
                            <?php endif; ?>
                            <p id="no-search-results" class="text-slate-500 col-span-full text-center py-10 hidden">No courses match your search.</p>
                        </div>
                    </div>
                    <div id="exam-list-container" class="px-4 pt-4"></div>
                </div>
            </main>
            <footer class="flex justify-center border-t border-solid border-slate-200 bg-white mt-auto">
                <div class="flex max-w-[960px] flex-1 flex-col px-5 py-10 text-center">
                    <p class="text-slate-500 text-sm font-normal leading-normal">© 2025 ExamPrep. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>
    <script src="assets/scripts.js"></script>
</body>
</html>