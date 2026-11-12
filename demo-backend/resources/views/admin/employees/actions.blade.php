<div class="dropdown">
    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenu{{ $row->id }}"
        data-bs-toggle="dropdown" aria-expanded="false">
        Actions
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenu{{ $row->id }}">
        <li>
            <a class="dropdown-item" href="{{ route('admin.employees.edit', $row->id) }}">
                Edit
            </a>
        </li>
        <li>
            <form action="{{ route('admin.employees.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger">
                    Delete
                </button>
            </form>
        </li>
    </ul>
</div>
