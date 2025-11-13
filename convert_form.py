import pypandoc
import os

def convert_markdown_file(input_file, output_format, output_file):
    """
    Converts a Markdown file to the specified output format using pandoc.

    Args:
        input_file (str): The path to the input Markdown file.
        output_format (str): The desired output format (e.g., 'pdf', 'docx').
        output_file (str): The path for the output file.
    """
    try:
        print(f"Converting {input_file} to {output_format.upper()}...")
        # For PDF conversion, you might need to specify a PDF engine, like 'pdflatex'
        # This requires a LaTeX distribution to be installed.
        extra_args = ['--pdf-engine=pdflatex'] if output_format == 'pdf' else []

        pypandoc.convert_file(
            input_file,
            output_format,
            outputfile=output_file,
            extra_args=extra_args
        )
        print(f"Successfully created {output_file}")
    except FileNotFoundError:
        print(f"Error: The file '{input_file}' was not found.")
    except OSError as e:
        print(f"Error converting to {output_format.upper()}: {e}")
        if 'pandoc' in str(e).lower():
            print("\\n**Pandoc Installation Note:**")
            print("This script requires Pandoc, a universal document converter.")
            print("Please make sure it is installed and in your system's PATH.")
            print("You can find installation instructions at: https://pandoc.org/installing.html")
        if 'pdflatex' in str(e).lower():
            print("\\n**LaTeX Installation Note for PDF conversion:**")
            print("To convert to PDF, a LaTeX distribution (like MiKTeX, TeX Live, or MacTeX) is required.")
            print("Please install one if you haven't already.")

def main():
    """
    Main function to handle the conversion of the UAT form.
    """
    markdown_file = 'UAT_Evaluation_Form.md'

    if not os.path.exists(markdown_file):
        print(f"Error: The source file '{markdown_file}' does not exist.")
        return

    # Convert to DOCX
    docx_output = 'UAT_Evaluation_Form.docx'
    convert_markdown_file(markdown_file, 'docx', docx_output)

    print("-" * 20)

    # Convert to PDF
    pdf_output = 'UAT_Evaluation_Form.pdf'
    convert_markdown_file(markdown_file, 'pdf', pdf_output)

if __name__ == '__main__':
    main()
