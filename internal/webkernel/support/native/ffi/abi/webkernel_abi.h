#ifndef WEBKERNEL_ABI_H
#define WEBKERNEL_ABI_H

#define WEBKERNEL_ABI_VERSION 1

typedef struct {
    const char *name;
    const char *version;
    int (*init)(void);
    int (*shutdown)(void);
} webkernel_module_info;

typedef struct {
    const char *name;
    void *fn;
} webkernel_function_entry;

#endif
