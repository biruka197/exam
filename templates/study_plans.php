<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPrep - Study Plans</title>
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
                        <div class="size-5">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path></svg>
                        </div>
                        <h2 class="text-slate-900 text-lg font-bold leading-tight tracking-[-0.015em]">ExamPrep</h2>
                    </a>
                    <div class="hidden sm:flex items-center gap-8">
                        <a class="text-slate-900 text-sm font-medium leading-normal" href="index.php">My Exams</a>
                        <a class="text-sky-600 text-sm font-bold leading-normal" href="index.php?page=study_plans">Study Plans</a>

                    </div>
                </div>
                 <div class="flex flex-1 justify-end items-center gap-2 sm:gap-4">
                     <button class="flex items-center justify-center rounded-lg h-10 w-10 bg-slate-100 text-slate-900">
                        <div class="size-5"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M221.8,175.94C216.25,166.38,208,139.33,208,104a80,80,0,1,0-160,0c0,35.34-8.26,62.38-13.81,71.94A16,16,0,0,0,48,200H88.81a40,40,0,0,0,78.38,0H208a16,16,0,0,0,13.8-24.06ZM128,216a24,24,0,0,1-22.62-16h45.24A24,24,0,0,1,128,216ZM48,184c7.7-13.24,16-43.92,16-80a64,64,0,1,1,128,0c0,36.05,8.28,66.73,16,80Z"></path></svg></div>
                    </button>
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("https://source.unsplash.com/random/100x100/?person");'></div>
                </div>
            </header>
            
            <main class="flex justify-center flex-1 px-4 sm:px-10 py-5">
                <div class="flex flex-col text-center max-w-[960px] flex-1 items-center justify-center">
                    <div class="text-sky-600 bg-sky-100 p-4 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48px" height="48px" fill="currentColor" viewBox="0 0 256 256"><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM96,176a8,8,0,0,1-16,0V144H48a8,8,0,0,1,0-16H80v-8a8,8,0,0,1,16,0v8h32a8,8,0,0,1,0,16H96Zm56-64a8,8,0,0,1-8,8H120a8,8,0,0,1,0-16h24A8,8,0,0,1,152,112Zm48,32a8,8,0,0,1-8,8H168a8,8,0,0,1,0-16h24A8,8,0,0,1,200,144Z"></path></svg>
                    </div>
                    <h1 class="text-slate-900 text-3xl font-bold tracking-tight">Study Plans</h1>
                    <p class="text-slate-500 mt-2">This feature is currently under development.</p>
                    <p class="text-slate-500">Check back soon to create and manage your personalized study schedules!</p>
                    <a href="index.php" class="mt-8 rounded-md border border-transparent bg-sky-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-sky-700">Go to My Exams</a>
                </div>
            </main>
        </div>
    </div>
</body>
</html>