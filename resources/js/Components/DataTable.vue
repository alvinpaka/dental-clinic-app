<script setup lang="ts">
import { h } from 'vue';
import {
  getCoreRowModel,
  useVueTable,
  FlexRender,  // Named import for component
} from '@tanstack/vue-table';
import type { ColumnDef } from '@tanstack/vue-table';  // Type-only
import { router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { MoreVertical } from 'lucide-vue-next';
import { Badge } from '@/Components/ui/badge';

interface Action {
  label: string;
  to?: string;
  onClick?: () => void;
  method?: string;
  variant?: string;
  icon?: any;
}

interface Props {
  columns: ColumnDef<any>[];
  data: any[];
  actions?: (row: any) => Action[];
  renderCell?: (columnKey: string, row: any) => { text?: string; variant?: string; icon?: any; } | string | null;
}

const props = defineProps<Props>();

const table = useVueTable({
  data: props.data,
  columns: props.columns,
  getCoreRowModel: getCoreRowModel(),
});

const handleAction = (action: Action, row?: any) => {
  if (action.onClick) {
    action.onClick();
  } else if (action.to) {
    router.visit(action.to, { method: action.method as any || 'get' });
  }
};

// Helper for custom cell rendering (returns string or VNode)
const renderCellContent = (columnKey: string, cell: any) => {
  const value = props.renderCell ? props.renderCell(columnKey, cell.row.original) : null;
  
  if (typeof value === 'string') {
    return value;
  }
  
  if (value && typeof value === 'object' && value.text) {
    const { text, variant = 'default', icon } = value;
    return h(Badge, { variant: variant as any }, [
      icon ? h(icon, { class: 'mr-1 h-3 w-3' }) : null,
      text,
    ]);
  }
  
  // Fallback to FlexRender as VNode (using h() - fixes "not a function")
  return h(FlexRender, {
    columnDef: cell.column.columnDef,
    context: cell.getContext(),
  });
};
</script>

<template>
  <div class="rounded-md border shadow-sm bg-white">
    <Table>
      <TableHeader class="bg-gray-100">
        <TableRow>
          <TableHead
            v-for="header in table.getHeaderGroups()[0]?.headers || []"
            :key="header.id"
            class="font-semibold py-3"
          >
            {{ header.column.columnDef.header }}
          </TableHead>
          <TableHead v-if="props.actions" class="text-right font-semibold py-3">
            Actions
          </TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="(row, index) in table.getRowModel().rows" :key="row.id" :class="index % 2 === 0 ? 'bg-white hover:bg-gray-100' : 'bg-gray-50 hover:bg-gray-100'" class="cursor-pointer">
          <TableCell
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            class="py-3"
          >
            <!-- Use <component :is> to handle string or VNode (fixes circular JSON error) -->
            <component :is="renderCellContent(cell.column.id, cell)" />
          </TableCell>
          <TableCell v-if="props.actions" class="text-right">
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="h-8 w-8 p-0">
                  <span class="sr-only">Open menu</span>
                  <MoreVertical class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  v-for="(action, index) in props.actions(row.original)"
                  :key="index"
                  as-child
                >
                  <button
                    @click="handleAction(action, row.original)"
                    :class="action.variant === 'destructive' ? 'text-red-600' : ''"
                    class="w-full text-left"
                  >
                    <component :is="action.icon" v-if="action.icon" class="mr-1 h-4 w-4" />
                    {{ action.label }}
                  </button>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>